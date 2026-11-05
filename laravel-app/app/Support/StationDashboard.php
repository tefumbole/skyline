<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StationDashboard
{
    public static function forUser($user)
    {
        $role = strtolower((string) ($user ? $user->roleName() : ''));
        if ($role === 'kitchen') {
            $role = 'restaurant';
        }
        if ($role === 'bartender') {
            $role = 'bar';
        }
        if (! in_array($role, ['restaurant', 'bar', 'waiter'], true)) {
            $role = 'restaurant';
        }
        $today = Carbon::now('Africa/Douala');
        $zone = config('app.timezone') ?: 'UTC';
        $from = $today->copy()->startOfDay()->timezone($zone)->format('Y-m-d H:i:s');
        $to = $today->copy()->endOfDay()->timezone($zone)->format('Y-m-d H:i:s');
        $board = [
            'role' => $role,
            'date' => $today->format('l j F'),
            'orders' => 0,
            'cook_seconds' => 0,
            'cook_count' => 0,
            'awaiting' => 0,
            'accepted' => 0,
            'ready' => 0,
            'delivered' => 0,
        ];
        if (! Schema::hasTable('restaurant_orders') || ! Schema::hasTable('restaurant_order_items')) {
            return $board;
        }
        if ($role === 'waiter') {
            return self::waiter($board, (int) $user->id, $from, $to);
        }

        return self::station($board, $role, $from, $to);
    }

    public static function clock($seconds)
    {
        $seconds = max(0, (int) $seconds);
        if ($seconds < 60) {
            return $seconds.' sec';
        }
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        if ($hours > 0) {
            return $hours.' h '.$minutes.' min';
        }

        return $minutes.' min';
    }

    protected static function station(array $board, $lane, $from, $to)
    {
        $placed = DB::table('restaurant_order_items as i')
            ->join('restaurant_orders as o', 'o.id', '=', 'i.restaurant_order_id')
            ->where('o.created_at', '>=', $from)
            ->where('o.created_at', '<=', $to)
            ->where('i.status', '<>', 'rejected');
        self::lane($placed, $lane);
        $placedRow = $placed->select(DB::raw('COUNT(DISTINCT o.id) as total'))->first();
        $board['orders'] = (int) ($placedRow ? $placedRow->total : 0);

        $cooked = DB::table('restaurant_order_items as i')
            ->whereNotNull('i.accepted_at')
            ->whereNotNull('i.ready_at')
            ->where('i.ready_at', '>=', $from)
            ->where('i.ready_at', '<=', $to)
            ->where('i.status', '<>', 'rejected');
        self::lane($cooked, $lane);
        $rows = $cooked->get(['i.accepted_at', 'i.ready_at']);
        foreach ($rows as $row) {
            $start = strtotime((string) $row->accepted_at);
            $end = strtotime((string) $row->ready_at);
            if ($start && $end && $end >= $start) {
                $board['cook_seconds'] += $end - $start;
                $board['cook_count']++;
            }
        }

        $open = DB::table('restaurant_order_items as i')->whereIn('i.status', ['awaiting', 'preparing', 'ready']);
        self::lane($open, $lane);
        foreach ($open->select('i.status', DB::raw('COUNT(*) as total'))->groupBy('i.status')->get() as $row) {
            if ($row->status === 'awaiting') {
                $board['awaiting'] = (int) $row->total;
            } elseif ($row->status === 'preparing') {
                $board['accepted'] = (int) $row->total;
            } elseif ($row->status === 'ready') {
                $board['ready'] = (int) $row->total;
            }
        }

        return $board;
    }

    protected static function waiter(array $board, $userId, $from, $to)
    {
        $board['orders'] = (int) DB::table('restaurant_orders as o')
            ->join('sales as s', 's.id', '=', 'o.sale_id')
            ->where('o.created_at', '>=', $from)
            ->where('o.created_at', '<=', $to)
            ->whereRaw('COALESCE(o.placed_by_user_id, s.user_id) = ?', [$userId])
            ->count();
        $board['ready'] = (int) DB::table('restaurant_order_items as i')
            ->join('restaurant_orders as o', 'o.id', '=', 'i.restaurant_order_id')
            ->join('sales as s', 's.id', '=', 'o.sale_id')
            ->where('i.status', 'ready')
            ->whereRaw('COALESCE(o.placed_by_user_id, s.user_id) = ?', [$userId])
            ->count();
        $deliveredRow = DB::table('restaurant_order_items as i')
            ->where('i.status', 'delivered')
            ->where('i.delivered_by', $userId)
            ->where('i.delivered_at', '>=', $from)
            ->where('i.delivered_at', '<=', $to)
            ->select(DB::raw('COUNT(DISTINCT i.restaurant_order_id) as total'))
            ->first();
        $board['delivered'] = (int) ($deliveredRow ? $deliveredRow->total : 0);
        $carried = DB::table('restaurant_order_items as i')
            ->where('i.delivered_by', $userId)
            ->whereNotNull('i.ready_at')
            ->whereNotNull('i.delivered_at')
            ->where('i.delivered_at', '>=', $from)
            ->where('i.delivered_at', '<=', $to)
            ->get(['i.ready_at', 'i.delivered_at']);
        foreach ($carried as $row) {
            $start = strtotime((string) $row->ready_at);
            $end = strtotime((string) $row->delivered_at);
            if ($start && $end && $end >= $start) {
                $board['cook_seconds'] += $end - $start;
                $board['cook_count']++;
            }
        }

        return $board;
    }

    protected static function lane($query, $lane)
    {
        if ($lane === 'bar') {
            $query->where(function ($items) {
                $items->whereIn('i.lane', ['bar', 'coffee'])
                    ->orWhere(function ($drinks) {
                        $drinks->where('i.department', 'drinks')->where(function ($older) {
                            $older->whereNull('i.lane')->orWhere('i.lane', 'online');
                        });
                    });
            });

            return;
        }
        $query->where(function ($items) {
            $items->where('i.lane', 'restaurant')
                ->orWhere(function ($food) {
                    $food->where('i.department', 'food')->where(function ($older) {
                        $older->whereNull('i.lane')->orWhere('i.lane', 'online');
                    });
                });
        });
    }
}
