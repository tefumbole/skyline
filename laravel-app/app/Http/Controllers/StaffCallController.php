<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffCallController extends Controller
{
    public function index()
    {
        $this->touch();

        return view('calls.index');
    }

    public function heartbeat()
    {
        $this->touch();
        $this->expire();
        $userId = (int) auth()->id();

        return response()->json([
            'ok' => true,
            'incoming' => $this->incomingPayload($userId),
        ]);
    }

    public function online()
    {
        $this->touch();
        $this->expire();
        $me = auth()->user();
        $mine = (string) DB::table('users')->where('id', $me->id)->value('last_seen_ip');
        $rows = $this->onlineQuery()
            ->where('users.id', '!=', $me->id)
            ->orderBy('users.name')
            ->limit(100)
            ->get(['users.id', 'users.name', 'users.last_seen_ip', 'roles.name as role_name']);

        $people = [];
        foreach ($rows as $row) {
            $people[] = [
                'id' => (int) $row->id,
                'name' => $row->name ?: 'Staff',
                'role' => $row->role_name ?: 'Staff',
                'same_network' => $this->sameNetwork($mine, $row->last_seen_ip),
            ];
        }

        return response()->json(['ok' => true, 'people' => $people]);
    }

    public function store(Request $request)
    {
        $this->touch();
        $this->expire();
        $me = (int) auth()->id();
        $calleeId = (int) $request->input('callee_user_id');
        $mode = $request->input('mode') === 'video' ? 'video' : ($request->input('mode') === 'audio' ? 'audio' : '');
        $offer = (string) $request->input('offer');
        if ($calleeId < 1 || $calleeId === $me || $mode === '' || $offer === '' || strlen($offer) > 60000) {
            return response()->json(['ok' => false, 'error' => 'invalid'], 422);
        }

        $callee = $this->onlineQuery()->where('users.id', $calleeId)->first([
            'users.id',
            'users.name',
            'roles.name as role_name',
        ]);
        if (! $callee) {
            return response()->json(['ok' => false, 'error' => 'offline'], 422);
        }
        if ($this->activeCallId($me) || $this->activeCallId($calleeId)) {
            return response()->json(['ok' => false, 'error' => 'busy'], 409);
        }

        $now = Carbon::now();
        $id = DB::table('staff_calls')->insertGetId([
            'caller_user_id' => $me,
            'callee_user_id' => $calleeId,
            'mode' => $mode,
            'status' => 'ringing',
            'offer' => $offer,
            'touched_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json([
            'ok' => true,
            'id' => (int) $id,
            'name' => $callee->name ?: 'Staff',
            'role' => ! empty($callee->role_name) ? $callee->role_name : 'Staff',
            'mode' => $mode,
        ]);
    }

    public function show(Request $request, $id)
    {
        $this->expire();
        $call = $this->findCall($id);
        if (! $call) {
            return response()->json(['ok' => false], 404);
        }
        if (in_array($call->status, ['ringing', 'accepted'], true)) {
            DB::table('staff_calls')->where('id', $call->id)->update(['touched_at' => Carbon::now()]);
        }

        $after = (int) $request->query('after', 0);
        $signals = DB::table('staff_call_signals')
            ->where('staff_call_id', $call->id)
            ->where('id', '>', $after)
            ->where('from_user_id', '!=', auth()->id())
            ->orderBy('id')
            ->limit(40)
            ->get(['id', 'payload']);
        $ice = [];
        foreach ($signals as $signal) {
            $payload = json_decode($signal->payload, true);
            if (! is_array($payload) || empty($payload['candidate'])) {
                continue;
            }
            $ice[] = [
                'id' => (int) $signal->id,
                'candidate' => (string) $payload['candidate'],
                'sdpMid' => isset($payload['sdpMid']) ? (string) $payload['sdpMid'] : null,
                'sdpMLineIndex' => isset($payload['sdpMLineIndex']) ? (int) $payload['sdpMLineIndex'] : null,
            ];
        }

        return response()->json([
            'ok' => true,
            'id' => (int) $call->id,
            'status' => $call->status,
            'mode' => $call->mode,
            'answer' => ((int) $call->caller_user_id === (int) auth()->id() && $request->query('need_answer')) ? $call->answer : null,
            'signals' => $ice,
        ]);
    }

    public function answer(Request $request, $id)
    {
        $call = $this->findCall($id);
        $answer = (string) $request->input('answer');
        if (! $call || (int) $call->callee_user_id !== (int) auth()->id() || $answer === '' || strlen($answer) > 60000) {
            return response()->json(['ok' => false], 404);
        }
        $now = Carbon::now();
        $updated = DB::table('staff_calls')
            ->where('id', $call->id)
            ->where('status', 'ringing')
            ->update([
                'status' => 'accepted',
                'answer' => $answer,
                'touched_at' => $now,
                'updated_at' => $now,
            ]);
        if (! $updated) {
            return response()->json(['ok' => false, 'error' => 'gone'], 409);
        }

        return response()->json(['ok' => true]);
    }

    public function ice(Request $request, $id)
    {
        $call = $this->findCall($id);
        if (! $call || ! in_array($call->status, ['ringing', 'accepted'], true)) {
            return response()->json(['ok' => false], 404);
        }
        $items = $request->input('candidates');
        if (! is_array($items)) {
            $items = [];
        }
        if ($request->input('candidate')) {
            $items[] = [
                'candidate' => $request->input('candidate'),
                'sdpMid' => $request->input('sdpMid'),
                'sdpMLineIndex' => $request->input('sdpMLineIndex'),
            ];
        }
        $now = Carbon::now();
        $saved = 0;
        foreach ($items as $item) {
            if ($saved >= 20 || ! is_array($item) || empty($item['candidate'])) {
                continue;
            }
            $candidate = (string) $item['candidate'];
            if (strlen($candidate) > 2000) {
                continue;
            }
            DB::table('staff_call_signals')->insert([
                'staff_call_id' => $call->id,
                'from_user_id' => auth()->id(),
                'payload' => json_encode([
                    'candidate' => $candidate,
                    'sdpMid' => isset($item['sdpMid']) ? (string) $item['sdpMid'] : '',
                    'sdpMLineIndex' => isset($item['sdpMLineIndex']) ? (int) $item['sdpMLineIndex'] : 0,
                ]),
                'created_at' => $now,
            ]);
            $saved++;
        }
        DB::table('staff_calls')->where('id', $call->id)->update(['touched_at' => $now]);

        return response()->json(['ok' => true]);
    }

    public function decline($id)
    {
        $call = $this->findCall($id);
        if (! $call || (int) $call->callee_user_id !== (int) auth()->id()) {
            return response()->json(['ok' => false], 404);
        }
        $now = Carbon::now();
        DB::table('staff_calls')->where('id', $call->id)->where('status', 'ringing')->update([
            'status' => 'declined',
            'ended_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json(['ok' => true]);
    }

    public function end($id)
    {
        $call = $this->findCall($id);
        if (! $call) {
            return response()->json(['ok' => false], 404);
        }
        $now = Carbon::now();
        DB::table('staff_calls')
            ->where('id', $call->id)
            ->whereIn('status', ['ringing', 'accepted'])
            ->update([
                'status' => 'ended',
                'ended_at' => $now,
                'updated_at' => $now,
            ]);

        return response()->json(['ok' => true]);
    }

    protected function touch()
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }
        $ip = substr((string) request()->ip(), 0, 45);
        DB::table('users')->where('id', $user->id)->update([
            'last_seen_at' => Carbon::now(),
            'last_seen_ip' => $ip,
        ]);
    }

    protected function expire()
    {
        $now = Carbon::now();
        DB::table('staff_calls')
            ->where('status', 'ringing')
            ->where('created_at', '<', $now->copy()->subSeconds(45))
            ->update(['status' => 'missed', 'ended_at' => $now, 'updated_at' => $now]);
        DB::table('staff_calls')
            ->where('status', 'accepted')
            ->where(function ($query) use ($now) {
                $query->where('touched_at', '<', $now->copy()->subSeconds(25))
                    ->orWhere(function ($inner) use ($now) {
                        $inner->whereNull('touched_at')->where('updated_at', '<', $now->copy()->subSeconds(25));
                    });
            })
            ->update(['status' => 'ended', 'ended_at' => $now, 'updated_at' => $now]);
    }

    protected function onlineQuery()
    {
        return DB::table('users')
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->where('users.is_active', 1)
            ->where(function ($query) {
                $query->where('users.is_deleted', 0)
                    ->orWhere('users.is_deleted', false)
                    ->orWhereNull('users.is_deleted');
            })
            ->where('users.last_seen_at', '>=', Carbon::now()->subSeconds(45));
    }

    protected function activeCallId($userId)
    {
        $row = DB::table('staff_calls')
            ->whereIn('status', ['ringing', 'accepted'])
            ->where(function ($query) use ($userId) {
                $query->where('caller_user_id', $userId)->orWhere('callee_user_id', $userId);
            })
            ->orderBy('id', 'desc')
            ->value('id');

        return $row ? (int) $row : 0;
    }

    protected function findCall($id)
    {
        $call = DB::table('staff_calls')->where('id', (int) $id)->first();
        if (! $call) {
            return null;
        }
        $userId = (int) auth()->id();
        if ((int) $call->caller_user_id !== $userId && (int) $call->callee_user_id !== $userId) {
            return null;
        }

        return $call;
    }

    protected function incomingPayload($userId)
    {
        $call = DB::table('staff_calls')
            ->where('callee_user_id', $userId)
            ->where('status', 'ringing')
            ->orderBy('id', 'desc')
            ->first();
        if (! $call) {
            return null;
        }
        $caller = DB::table('users')
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->where('users.id', $call->caller_user_id)
            ->first(['users.name', 'roles.name as role_name']);

        return [
            'id' => (int) $call->id,
            'mode' => $call->mode,
            'offer' => $call->offer,
            'name' => $caller && $caller->name ? $caller->name : 'Staff',
            'role' => $caller && $caller->role_name ? $caller->role_name : 'Staff',
        ];
    }

    protected function sameNetwork($mine, $theirs)
    {
        $mine = trim((string) $mine);
        $theirs = trim((string) $theirs);
        if ($mine === '' || $theirs === '' || $mine === '127.0.0.1') {
            return false;
        }
        if ($mine === $theirs) {
            return true;
        }
        $a = explode('.', $mine);
        $b = explode('.', $theirs);
        if (count($a) !== 4 || count($b) !== 4) {
            return false;
        }
        $private = ($this->isPrivate($a) && $this->isPrivate($b));

        return $private && $a[0] === $b[0] && $a[1] === $b[1] && $a[2] === $b[2];
    }

    protected function isPrivate(array $parts)
    {
        $first = (int) $parts[0];
        $second = (int) $parts[1];

        return $first === 10
            || ($first === 192 && $second === 168)
            || ($first === 172 && $second >= 16 && $second <= 31);
    }
}
