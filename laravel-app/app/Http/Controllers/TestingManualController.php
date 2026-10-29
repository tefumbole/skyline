<?php

namespace App\Http\Controllers;

use App\ManualTestReport;
use App\ManualTestResult;
use App\Support\SystemTestScore;
use App\Support\TestingManual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TestingManualController extends Controller
{
    public function index(Request $request)
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
        $tab = $request->query('tab') === 'results' ? 'results' : 'guide';
        if ($tab === 'results' && ! $full) {
            abort(403);
        }

        $rows = [];
        $siteScore = null;
        if ($full && Schema::hasTable('manual_test_reports')) {
            $reports = ManualTestReport::orderBy('id', 'desc')->limit(50)->get();
            foreach ($reports as $report) {
                $payload = json_decode((string) $report->payload, true);
                $items = isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : [];
                $summary = SystemTestScore::summarize($items);
                $rows[] = ['report' => $report, 'summary' => $summary];
                if ($siteScore === null && $report->status === 'submitted' && $summary['complete']) {
                    $siteScore = $summary;
                    $siteScore['name'] = $report->tester_name;
                    $siteScore['when'] = $report->updated_at;
                }
            }
        }

        return view('manual.testing', [
            'sections' => $sections,
            'saved' => $saved,
            'total' => count(TestingManual::keysFrom($sections)),
            'shareUrl' => $full ? url('/system-test') : null,
            'tab' => $tab,
            'rows' => $rows,
            'siteScore' => $siteScore,
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

    public function report($id)
    {
        $user = auth()->user();
        if (! $user || ! in_array((int) $user->role_id, [1, 2], true)) {
            abort(403);
        }
        $report = ManualTestReport::findOrFail($id);
        $payload = json_decode((string) $report->payload, true);
        $items = isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : [];

        return view('manual.report', [
            'report' => $report,
            'summary' => SystemTestScore::summarize($items),
        ]);
    }
}
