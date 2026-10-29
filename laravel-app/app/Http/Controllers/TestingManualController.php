<?php

namespace App\Http\Controllers;

use App\ManualTestReport;
use App\ManualTestResult;
use App\Support\TestingManual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TestingManualController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }

        $sections = TestingManual::forUser($user);
        $saved = collect();
        if (Schema::hasTable('manual_test_results')) {
            $saved = ManualTestResult::where('user_id', $user->id)->get()->keyBy('item_key');
        }

        $full = in_array((int) $user->role_id, [1, 2], true);
        $reports = collect();
        if ($full && Schema::hasTable('manual_test_reports')) {
            $reports = ManualTestReport::orderBy('id', 'desc')->limit(20)->get();
        }

        return view('manual.testing', [
            'sections' => $sections,
            'saved' => $saved,
            'total' => count(TestingManual::keysFrom($sections)),
            'shareUrl' => $full ? url('/system-test') : null,
            'reports' => $reports,
        ]);
    }

    public function save(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }
        if (! Schema::hasTable('manual_test_results')) {
            return response()->json(['ok' => false, 'message' => 'The checklist is not ready yet.'], 503);
        }

        $key = (string) $request->input('item_key');
        $allowed = TestingManual::keysFrom(TestingManual::forUser($user));
        if (! in_array($key, $allowed, true)) {
            abort(403);
        }

        $result = $request->input('result');
        if ($result === null || $result === '') {
            ManualTestResult::where('user_id', $user->id)->where('item_key', $key)->delete();

            return response()->json(['ok' => true, 'result' => '']);
        }
        if (! in_array($result, ['pass', 'fail'], true)) {
            abort(422);
        }

        ManualTestResult::updateOrCreate(
            ['user_id' => $user->id, 'item_key' => $key],
            ['result' => $result]
        );

        return response()->json(['ok' => true, 'result' => $result]);
    }

    public function publicForm()
    {
        return view('manual.system_test', [
            'sections' => TestingManual::sections(),
            'sent' => session('message'),
        ]);
    }

    public function publicStore(Request $request)
    {
        if (! Schema::hasTable('manual_test_reports')) {
            return redirect()->route('system.test')->with('message', 'The test form is not ready yet. Try again in a minute.');
        }

        $name = trim((string) $request->input('tester_name'));
        if ($name === '' || mb_strlen($name) > 120) {
            return redirect()->route('system.test')->with('message', 'Enter your name, then send the report again.');
        }

        $known = array_flip(TestingManual::keys());
        $items = [];
        $posted = (array) $request->input('items', []);
        $notes = (array) $request->input('notes', []);
        foreach ($posted as $key => $result) {
            if (! isset($known[$key]) || ! in_array($result, ['pass', 'fail'], true)) {
                continue;
            }
            $note = isset($notes[$key]) ? trim((string) $notes[$key]) : '';
            $items[$key] = [
                'result' => $result,
                'note' => $note !== '' ? mb_substr($note, 0, 500) : '',
            ];
        }

        $report = ManualTestReport::create([
            'tester_name' => $name,
            'payload' => json_encode(['items' => $items]),
        ]);

        return redirect()->route('system.test')->with('message', 'Thank you, '.$name.'. Report '.$report->id.' was saved.');
    }

    public function report($id)
    {
        $user = auth()->user();
        if (! $user || ! in_array((int) $user->role_id, [1, 2], true)) {
            abort(403);
        }
        $report = ManualTestReport::findOrFail($id);
        $payload = json_decode((string) $report->payload, true);
        $items = isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : [];
        $titles = [];
        foreach (TestingManual::sections() as $section) {
            foreach ($section['items'] as $item) {
                $titles[$item['key']] = $section['title'].' — '.$item['title'];
            }
        }

        return view('manual.report', [
            'report' => $report,
            'items' => $items,
            'titles' => $titles,
        ]);
    }
}
