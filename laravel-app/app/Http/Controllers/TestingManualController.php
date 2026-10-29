<?php

namespace App\Http\Controllers;

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

        $saved = collect();
        if (Schema::hasTable('manual_test_results')) {
            $saved = ManualTestResult::where('user_id', $user->id)->get()->keyBy('item_key');
        }

        return view('manual.testing', [
            'sections' => TestingManual::sections(),
            'saved' => $saved,
            'total' => count(TestingManual::keys()),
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
        if (! TestingManual::has($key)) {
            abort(404);
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
}
