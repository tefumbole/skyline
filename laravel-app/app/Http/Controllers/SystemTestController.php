<?php

namespace App\Http\Controllers;

use App\ManualTestReport;
use App\Services\SystemTestIdentity;
use App\Services\SystemTestOtp;
use App\Support\SystemTestScore;
use App\Support\TestingManual;
use App\Support\WhatsAppPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SystemTestController extends Controller
{
    public function show(Request $request)
    {
        if (! $this->ready()) {
            return view('manual.system_test', ['step' => 'unavailable', 'error' => null, 'notice' => null]);
        }

        $verified = session('system_test');
        if (is_array($verified) && ! empty($verified['phone'])) {
            if ($request->query('done')) {
                return $this->doneView();
            }

            return $this->stageView((int) $request->query('stage', 0));
        }

        $pending = session('system_test_pending');
        if (is_array($pending) && ($pending['step'] ?? '') === 'otp') {
            return view('manual.system_test', [
                'step' => 'otp',
                'error' => session('error'),
                'notice' => session('message'),
                'phone' => WhatsAppPhone::display($pending['phone']),
                'name' => $pending['name'],
            ]);
        }
        if (is_array($pending) && ($pending['step'] ?? '') === 'confirm') {
            return view('manual.system_test', [
                'step' => 'confirm',
                'error' => session('error'),
                'notice' => session('message'),
                'phone' => WhatsAppPhone::display($pending['phone']),
                'name' => $pending['name'],
                'source' => $pending['source'],
            ]);
        }

        return view('manual.system_test', [
            'step' => 'phone',
            'error' => session('error'),
            'notice' => session('message'),
        ]);
    }

    public function phone(Request $request)
    {
        if (! $this->ready()) {
            return redirect()->route('system.test');
        }
        try {
            $found = app(SystemTestIdentity::class)->resolve($request->input('phone'));
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('system.test')->with('error', $e->getMessage());
        }

        if ($found['source'] === 'manual') {
            $existing = ManualTestReport::where('phone', $found['phone'])->orderBy('id', 'desc')->first();
            if ($existing && trim((string) $existing->tester_name) !== '') {
                $found['name'] = $existing->tester_name;
                $found['source'] = 'saved';
            }
        }
        $found['step'] = 'confirm';
        session(['system_test_pending' => $found]);

        return redirect()->route('system.test');
    }

    public function sendCode(Request $request)
    {
        $pending = session('system_test_pending');
        if (! is_array($pending) || empty($pending['phone'])) {
            return redirect()->route('system.test')->with('error', 'Enter your WhatsApp number first.');
        }

        $name = trim((string) ($pending['name'] ?? ''));
        if (in_array($pending['source'] ?? '', ['manual', 'saved'], true) && ($pending['step'] ?? '') !== 'otp') {
            $name = trim((string) $request->input('tester_name'));
        }
        if ($name === '' || mb_strlen($name) > 120) {
            return redirect()->route('system.test')->with('error', 'Enter your name.');
        }

        $pending['name'] = $name;
        $pending['step'] = 'otp';
        session(['system_test_pending' => $pending]);

        try {
            app(SystemTestOtp::class)->send($pending['phone']);
        } catch (\RuntimeException $e) {
            $pending['step'] = 'confirm';
            session(['system_test_pending' => $pending]);

            return redirect()->route('system.test')->with('error', $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('system.test')->with('error', 'Enter a valid WhatsApp number.');
        }

        return redirect()->route('system.test')->with('message', 'A code was sent on WhatsApp.');
    }

    public function verify(Request $request)
    {
        $pending = session('system_test_pending');
        if (! is_array($pending) || empty($pending['phone']) || trim((string) ($pending['name'] ?? '')) === '') {
            return redirect()->route('system.test')->with('error', 'Enter your WhatsApp number first.');
        }

        try {
            $phone = app(SystemTestOtp::class)->verify($pending['phone'], $request->input('code'));
        } catch (\RuntimeException $e) {
            return redirect()->route('system.test')->with('error', $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('system.test')->with('error', 'Enter a valid WhatsApp number.');
        }

        $report = $this->resume($phone, $pending['name']);
        session()->forget('system_test_pending');
        session(['system_test' => ['phone' => $phone, 'report_id' => $report->id]]);

        $stage = (int) $report->stage;
        if ($stage < 1) {
            $stage = 1;
        }

        return redirect()->route('system.test', ['stage' => $stage]);
    }

    public function saveStage(Request $request)
    {
        $report = $this->currentReport();
        if (! $report) {
            return redirect()->route('system.test')->with('error', 'Confirm your WhatsApp number to continue this test.');
        }

        $sections = TestingManual::sections();
        $count = count($sections);
        $stage = (int) $request->input('stage');
        if ($stage < 1 || $stage > $count) {
            abort(422);
        }

        $section = $sections[$stage - 1];
        $payload = json_decode((string) $report->payload, true);
        $items = isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : [];
        $posted = (array) $request->input('items', []);
        $notes = (array) $request->input('notes', []);

        foreach ($section['items'] as $item) {
            $key = $item['key'];
            $result = isset($posted[$key]) ? (string) $posted[$key] : '';
            $note = isset($notes[$key]) ? trim((string) $notes[$key]) : '';
            $note = $note !== '' ? mb_substr($note, 0, 500) : '';
            if (! in_array($result, ['pass', 'fail'], true)) {
                if (isset($items[$key]) && is_array($items[$key])) {
                    $items[$key]['note'] = $note;
                }
                continue;
            }
            $items[$key] = ['result' => $result, 'note' => $note];
        }

        $report->payload = json_encode(['items' => $items]);
        $report->tester_name = mb_substr(trim((string) $report->tester_name), 0, 120);
        $action = (string) $request->input('action');

        if ($action === 'back') {
            $report->stage = max(1, $stage - 1);
            $report->status = 'draft';
            $report->save();

            return redirect()->route('system.test', ['stage' => $report->stage]);
        }

        if ($action === 'finish') {
            $summary = SystemTestScore::summarize($items);
            if (! $summary['complete']) {
                $first = (int) $summary['open'][0]['stage'];
                $report->stage = $first;
                $report->status = 'draft';
                $report->save();
                $left = count($summary['open']);

                return redirect()->route('system.test', ['stage' => $first])->with(
                    'error',
                    'Every check has to be marked before the site score is final. '.$left.' '.($left === 1 ? 'check is' : 'checks are').' still open.'
                );
            }
            $report->stage = $count;
            $report->status = 'submitted';
            $report->save();
            $sent = $this->sendSummary($report, $summary);
            $notice = $sent
                ? 'The summary was sent to your WhatsApp.'
                : 'The report is saved. WhatsApp could not send the summary.';

            return redirect()->route('system.test', ['done' => 1])->with('message', $notice);
        }

        $report->stage = min($count, $stage + 1);
        $report->status = 'draft';
        $report->save();

        return redirect()->route('system.test', ['stage' => $report->stage])->with('message', 'Stage '.$stage.' was saved.');
    }

    public function reset()
    {
        session()->forget(['system_test', 'system_test_pending']);

        return redirect()->route('system.test');
    }

    protected function stageView($requested)
    {
        $report = $this->currentReport();
        if (! $report) {
            session()->forget('system_test');

            return redirect()->route('system.test');
        }

        $sections = TestingManual::sections();
        $count = count($sections);
        $stage = $requested > 0 ? $requested : (int) $report->stage;
        if ($stage < 1) {
            $stage = 1;
        }
        if ($stage > $count) {
            $stage = $count;
        }

        $payload = json_decode((string) $report->payload, true);
        $items = isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : [];
        $summary = SystemTestScore::summarize($items);

        return view('manual.system_test', [
            'step' => 'stage',
            'error' => session('error'),
            'notice' => session('message'),
            'stage' => $stage,
            'stageCount' => $count,
            'section' => $sections[$stage - 1],
            'saved' => $items,
            'report' => $report,
            'summary' => $summary,
            'phone' => WhatsAppPhone::display($report->phone),
        ]);
    }

    protected function doneView()
    {
        $report = $this->currentReport();
        if (! $report) {
            return redirect()->route('system.test');
        }
        $payload = json_decode((string) $report->payload, true);
        $items = isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : [];

        return view('manual.system_test', [
            'step' => 'done',
            'error' => session('error'),
            'notice' => session('message'),
            'report' => $report,
            'summary' => SystemTestScore::summarize($items),
            'phone' => WhatsAppPhone::display($report->phone),
        ]);
    }

    protected function resume($phone, $name)
    {
        $name = mb_substr(trim($name), 0, 120);
        $report = ManualTestReport::where('phone', $phone)->orderBy('id', 'desc')->first();
        if (! $report) {
            return ManualTestReport::create([
                'tester_name' => $name,
                'phone' => $phone,
                'status' => 'draft',
                'stage' => 1,
                'payload' => json_encode(['items' => []]),
            ]);
        }
        $report->tester_name = $name;
        if ((int) $report->stage < 1) {
            $report->stage = 1;
        }
        $report->save();

        return $report;
    }

    protected function currentReport()
    {
        $verified = session('system_test');
        if (! is_array($verified) || empty($verified['report_id']) || empty($verified['phone'])) {
            return null;
        }
        $report = ManualTestReport::find($verified['report_id']);
        if (! $report || (string) $report->phone !== (string) $verified['phone']) {
            return null;
        }

        return $report;
    }

    protected function sendSummary(ManualTestReport $report, array $summary)
    {
        try {
            $result = app(\App\Services\Messaging\NotificationRouter::class)->sendWhatsAppText(
                $report->phone,
                SystemTestScore::message($report->tester_name, $summary)
            );
        } catch (\Throwable $e) {
            return false;
        }

        return ! empty($result['success']);
    }

    protected function ready()
    {
        return Schema::hasTable('manual_test_reports') && Schema::hasColumn('manual_test_reports', 'phone');
    }
}
