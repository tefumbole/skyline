<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FloorDashboard
{
    public static function blank(array $money = [])
    {
        return [
            'sales' => (float) (isset($money['sales']) ? $money['sales'] : 0),
            'cash' => (float) (isset($money['cash']) ? $money['cash'] : 0),
            'momo' => (float) (isset($money['momo']) ? $money['momo'] : 0),
            'due' => (float) (isset($money['due']) ? $money['due'] : 0),
            'customers' => 0,
            'orders' => 0,
            'staff' => [],
            'date' => Carbon::now('Africa/Douala')->format('l j F'),
        ];
    }

    public static function compose($startAt, $endAt, array $money, $sales)
    {
        $floor = self::blank($money);
        $live = collect($sales)->filter(function ($sale) {
            return (int) $sale->sale_status !== 3;
        })->values();
        $floor['customers'] = $live->pluck('customer_id')->filter(function ($id) {
            return (int) $id > 0;
        })->unique()->count();

        if (! Schema::hasTable('restaurant_orders') || ! Schema::hasTable('restaurant_order_items')) {
            return $floor;
        }

        $saleIds = $live->pluck('id')->map(function ($id) {
            return (int) $id;
        })->filter()->values()->all();
        if ($saleIds) {
            $floor['orders'] = (int) DB::table('restaurant_orders')->whereIn('sale_id', $saleIds)->count();
        }

        $zone = config('app.timezone') ?: 'UTC';
        $from = $startAt->copy()->timezone($zone)->format('Y-m-d H:i:s');
        $to = $endAt->copy()->timezone($zone)->format('Y-m-d H:i:s');
        $people = [];

        if ($saleIds) {
            $placed = DB::table('restaurant_orders as o')
                ->join('sales as s', 's.id', '=', 'o.sale_id')
                ->whereIn('o.sale_id', $saleIds)
                ->select(DB::raw('COALESCE(o.placed_by_user_id, s.user_id) as uid'), DB::raw('COUNT(*) as total'))
                ->groupBy(DB::raw('COALESCE(o.placed_by_user_id, s.user_id)'))
                ->get();
            foreach ($placed as $row) {
                self::bump($people, $row->uid, 'placed', (int) $row->total);
            }

            $waiting = DB::table('restaurant_orders as o')
                ->join('sales as s', 's.id', '=', 'o.sale_id')
                ->whereIn('o.sale_id', $saleIds)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('restaurant_order_items as i')
                        ->whereRaw('i.restaurant_order_id = o.id')
                        ->whereIn('i.status', ['awaiting', 'preparing', 'ready', 'hold']);
                })
                ->select(DB::raw('COALESCE(o.placed_by_user_id, s.user_id) as uid'), DB::raw('COUNT(*) as total'))
                ->groupBy(DB::raw('COALESCE(o.placed_by_user_id, s.user_id)'))
                ->get();
            foreach ($waiting as $row) {
                self::bump($people, $row->uid, 'waiting', (int) $row->total);
            }
        }

        $delivered = DB::table('restaurant_order_items as i')
            ->where('i.status', 'delivered')
            ->whereNotNull('i.delivered_by')
            ->where('i.delivered_at', '>=', $from)
            ->where('i.delivered_at', '<=', $to)
            ->select('i.delivered_by as uid', DB::raw('COUNT(DISTINCT i.restaurant_order_id) as total'))
            ->groupBy('i.delivered_by')
            ->get();
        foreach ($delivered as $row) {
            self::bump($people, $row->uid, 'delivered', (int) $row->total);
        }

        if (! $people) {
            return $floor;
        }

        $names = DB::table('users')->whereIn('id', array_keys($people))->pluck('name', 'id');
        $rows = [];
        foreach ($people as $id => $counts) {
            $name = trim((string) (isset($names[$id]) ? $names[$id] : ''));
            $rows[] = [
                'name' => $name !== '' ? $name : 'Staff',
                'waiting' => (int) $counts['waiting'],
                'placed' => (int) $counts['placed'],
                'delivered' => (int) $counts['delivered'],
            ];
        }
        usort($rows, function ($a, $b) {
            $left = $a['placed'] + $a['delivered'] + $a['waiting'];
            $right = $b['placed'] + $b['delivered'] + $b['waiting'];
            if ($left === $right) {
                return strcasecmp($a['name'], $b['name']);
            }

            return $left < $right ? 1 : -1;
        });
        $floor['staff'] = array_slice($rows, 0, 12);

        return $floor;
    }

    protected static function bump(array &$people, $userId, $field, $total)
    {
        $id = (int) $userId;
        if ($id < 1 || $total < 1) {
            return;
        }
        if (! isset($people[$id])) {
            $people[$id] = ['waiting' => 0, 'placed' => 0, 'delivered' => 0];
        }
        $people[$id][$field] += $total;
    }
}
