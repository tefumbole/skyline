<?php

namespace App\Support;

use App\RestaurantOrder;
use Illuminate\Support\Facades\Schema;

class TableBoard
{
    public static function canView($user)
    {
        if (! $user) {
            return false;
        }
        if ($user->hasFullAdminAccess()) {
            return true;
        }

        return in_array(strtolower((string) $user->roleName()), ['waiter', 'accountant', 'manager'], true);
    }

    public static function canPay($user)
    {
        if (! $user) {
            return false;
        }
        if ($user->hasFullAdminAccess()) {
            return true;
        }

        return in_array(strtolower((string) $user->roleName()), ['waiter', 'accountant', 'manager'], true);
    }

    public static function money($amount)
    {
        $amount = (float) $amount;
        if (abs($amount - round($amount)) < 0.001) {
            return number_format($amount, 0);
        }

        return number_format($amount, 2);
    }

    public static function rows()
    {
        $grouped = [];
        foreach (self::bills() as $bill) {
            $id = $bill['table_id'];
            if (! isset($grouped[$id])) {
                $grouped[$id] = [
                    'id' => $id,
                    'name' => $bill['table_name'],
                    'description' => $bill['table_description'],
                    'due' => 0,
                    'total' => 0,
                    'bills' => 0,
                    'open' => 0,
                    'latest' => $bill['when'],
                    'today' => false,
                ];
            }
            $grouped[$id]['bills']++;
            $grouped[$id]['due'] += $bill['due'];
            $grouped[$id]['total'] = (isset($grouped[$id]['total']) ? $grouped[$id]['total'] : 0) + $bill['total'];
            if ($bill['due'] > 0.009) {
                $grouped[$id]['open']++;
            }
            if ($bill['today']) {
                $grouped[$id]['today'] = true;
            }
            if ($bill['when'] > $grouped[$id]['latest']) {
                $grouped[$id]['latest'] = $bill['when'];
            }
        }

        $rows = [];
        foreach ($grouped as $row) {
            if ($row['open'] < 1) {
                continue;
            }
            $row['status'] = 'Pending';
            $rows[] = $row;
        }

        usort($rows, function ($a, $b) {
            if ($a['status'] !== $b['status']) {
                return $a['status'] === 'Pending' ? -1 : 1;
            }
            if (abs($a['due'] - $b['due']) > 0.009) {
                return $a['due'] < $b['due'] ? 1 : -1;
            }

            return strnatcasecmp($a['name'], $b['name']);
        });

        return $rows;
    }

    public static function table($id)
    {
        $id = (int) $id;
        $bills = [];
        $name = '';
        $description = '';
        foreach (self::bills() as $bill) {
            if ((int) $bill['table_id'] !== $id) {
                continue;
            }
            $name = $bill['table_name'];
            $description = $bill['table_description'];
            $bills[] = $bill;
        }
        if (! $bills) {
            return null;
        }
        $due = 0;
        foreach ($bills as $bill) {
            $due += $bill['due'];
        }
        usort($bills, function ($a, $b) {
            return strcmp($b['when'], $a['when']);
        });

        return [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'due' => $due,
            'status' => $due > 0.009 ? 'Pending' : 'Paid',
            'bills' => $bills,
        ];
    }

    protected static function bills()
    {
        if (! Schema::hasTable('restaurant_orders') || ! Schema::hasTable('sales')) {
            return [];
        }
        $today = date('Y-m-d');
        $orders = RestaurantOrder::with(['sale.customer', 'diningTable', 'items', 'placedBy'])
            ->whereNotNull('restaurant_table_id')
            ->whereNotNull('sale_id')
            ->where(function ($query) use ($today) {
                $query->whereDate('created_at', $today)
                    ->orWhereHas('sale', function ($sale) {
                        $sale->where('sale_status', 1)->where('payment_status', '!=', 4);
                    });
            })
            ->orderByDesc('id')
            ->get();

        $bills = [];
        foreach ($orders as $order) {
            $sale = $order->sale;
            $table = $order->diningTable;
            if (! $sale || ! $table || (int) $sale->sale_status === 3 || isset($bills[$sale->id])) {
                continue;
            }
            $due = max(0, (float) $sale->grand_total - (float) $sale->paid_amount);
            $when = $sale->created_at ? $sale->created_at->format('Y-m-d H:i:s') : (string) $order->created_at;
            $names = [];
            $menu = [];
            foreach ($order->items as $item) {
                if (in_array($item->status, ['cancelled', 'rejected'], true)) {
                    continue;
                }
                $names[] = trim($item->product_name.' × '.($item->qty + 0));
                $menu[] = self::menuLine($item);
            }
            $bills[$sale->id] = [
                'sale_id' => $sale->id,
                'reference' => $sale->reference_no,
                'customer' => optional($sale->customer)->name ?: '',
                'phone' => optional($sale->customer)->phone_number ?: '',
                'total' => (float) $sale->grand_total,
                'paid' => (float) $sale->paid_amount,
                'due' => $due,
                'status' => $due > 0.009 ? 'Pending' : 'Paid',
                'when' => $when,
                'when_label' => $sale->created_at ? $sale->created_at->format('j M Y H:i') : '',
                'today' => substr($when, 0, 10) === $today,
                'items' => $names,
                'menu' => $menu,
                'comment' => trim((string) $order->customer_comment),
                'placed' => $order->placedBy ? (string) $order->placedBy->name : '',
                'table_id' => $table->id,
                'table_name' => $table->name,
                'table_description' => $table->description ?: '',
            ];
        }

        return array_values($bills);
    }

    protected static function menuLine($item)
    {
        $state = (string) $item->status;
        $dueAt = $item->expected_ready_at ? $item->expected_ready_at->getTimestamp() : 0;
        $readyAt = $item->ready_at ? $item->ready_at->getTimestamp() : 0;
        $now = time();
        if ($state === 'preparing') {
            if ($dueAt) {
                $mins = (int) ceil(($dueAt - $now) / 60);
                $label = $mins > 0 ? 'Accepted. '.$mins.' min before delivery' : 'Accepted. Due now';
            } else {
                $label = 'Accepted';
            }
        } elseif ($state === 'ready') {
            $mins = $readyAt ? (int) floor(max(0, $now - $readyAt) / 60) : 0;
            $label = 'Marked for delivery. '.($mins < 1 ? 'Just now' : $mins.' min');
        } elseif ($state === 'delivered') {
            $label = 'Delivered';
        } elseif ($state === 'hold') {
            $label = 'On hold';
        } else {
            $label = 'Waiting to be accepted';
        }

        return [
            'kind' => $item->department === 'drinks' ? 'Drink' : 'Food',
            'name' => $item->product_name,
            'qty' => $item->qty + 0,
            'note' => trim((string) $item->customer_comment),
            'state' => $state,
            'label' => $label,
            'due_at' => $dueAt,
            'ready_at' => $readyAt,
        ];
    }

    /**
     * Sold, paid, and still due for each table in a window, ranked by amount.
     */
    public static function summary($startAt, $endAt)
    {
        $empty = [
            'rows' => [],
            'sold' => 0,
            'paid' => 0,
            'pending' => 0,
            'bills' => 0,
            'top_amount' => null,
            'top_frequency' => null,
        ];
        if (! Schema::hasTable('restaurant_orders') || ! Schema::hasTable('sales') || ! Schema::hasTable('restaurant_tables')) {
            return $empty;
        }
        $zone = config('app.timezone') ?: 'UTC';
        $from = $startAt->copy()->timezone($zone)->format('Y-m-d H:i:s');
        $to = $endAt->copy()->timezone($zone)->format('Y-m-d H:i:s');
        $orders = \DB::table('restaurant_orders as o')
            ->join('sales as s', 's.id', '=', 'o.sale_id')
            ->join('restaurant_tables as t', 't.id', '=', 'o.restaurant_table_id')
            ->whereNotNull('o.restaurant_table_id')
            ->where('s.sale_status', '<>', 3)
            ->where('s.created_at', '>=', $from)
            ->where('s.created_at', '<=', $to)
            ->get(['t.id as table_id', 't.name', 's.id as sale_id', 's.grand_total', 's.paid_amount']);

        $grouped = [];
        $seen = [];
        foreach ($orders as $order) {
            $saleId = (int) $order->sale_id;
            if (isset($seen[$saleId])) {
                continue;
            }
            $seen[$saleId] = true;
            $id = (int) $order->table_id;
            if (! isset($grouped[$id])) {
                $grouped[$id] = [
                    'id' => $id,
                    'name' => $order->name,
                    'bills' => 0,
                    'sold' => 0.0,
                    'paid' => 0.0,
                    'pending' => 0.0,
                ];
            }
            $sold = (float) $order->grand_total;
            $paid = max(0, (float) $order->paid_amount);
            $grouped[$id]['bills']++;
            $grouped[$id]['sold'] += $sold;
            $grouped[$id]['paid'] += $paid;
            $grouped[$id]['pending'] += max(0, $sold - $paid);
        }

        $rows = array_values($grouped);
        usort($rows, function ($a, $b) {
            if (abs($a['sold'] - $b['sold']) > 0.009) {
                return $a['sold'] < $b['sold'] ? 1 : -1;
            }
            if ($a['bills'] !== $b['bills']) {
                return $a['bills'] < $b['bills'] ? 1 : -1;
            }

            return strnatcasecmp($a['name'], $b['name']);
        });

        $sold = 0;
        $paid = 0;
        $pending = 0;
        $bills = 0;
        $topFrequency = null;
        foreach ($rows as $index => $row) {
            $rows[$index]['rank'] = $index + 1;
            $sold += $row['sold'];
            $paid += $row['paid'];
            $pending += $row['pending'];
            $bills += $row['bills'];
            if (! $topFrequency || $row['bills'] > $topFrequency['bills']) {
                $topFrequency = $row;
            }
        }

        return [
            'rows' => $rows,
            'sold' => $sold,
            'paid' => $paid,
            'pending' => $pending,
            'bills' => $bills,
            'top_amount' => isset($rows[0]) && $rows[0]['sold'] > 0.009 ? $rows[0] : null,
            'top_frequency' => $topFrequency && $topFrequency['bills'] > 0 ? $topFrequency : null,
        ];
    }
}
