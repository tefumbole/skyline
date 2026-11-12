<?php

namespace App\Support;

use App\RestaurantOrder;
use App\Sale;
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

    /**
     * A payment was chosen. The bill leaves the table and stays due in Sales and Payments
     * until money is actually recorded.
     */
    public static function releaseToCashier(Sale $sale)
    {
        if (Schema::hasTable('restaurant_orders') && Schema::hasColumn('restaurant_orders', 'cashier_at')) {
            RestaurantOrder::where('sale_id', $sale->id)
                ->whereNull('cashier_at')
                ->update(['cashier_at' => date('Y-m-d H:i:s')]);
        }
        $paid = (float) $sale->paid_amount;
        $due = (float) $sale->grand_total - $paid;
        if ($due <= 0.009 || (int) $sale->payment_status === 4) {
            return $sale;
        }
        $sale->sale_status = 1;
        if ($paid <= 0.009) {
            $sale->payment_status = 2;
        } elseif ((int) $sale->payment_status === 1) {
            $sale->payment_status = 3;
        }
        $sale->save();

        return $sale;
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
                    'states' => [],
                    'food' => [],
                ];
            }
            $grouped[$id]['bills']++;
            $grouped[$id]['due'] += $bill['due'];
            $grouped[$id]['total'] = (isset($grouped[$id]['total']) ? $grouped[$id]['total'] : 0) + $bill['total'];
            $grouped[$id]['states'][] = $bill['order_status'];
            if ($bill['due'] > 0.009) {
                $grouped[$id]['open']++;
            }
            foreach ($bill['menu'] as $line) {
                $grouped[$id]['food'][] = $line;
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
            if (count($row['food']) < 1) {
                continue;
            }
            $status = self::orderStatus($row['food']);
            $row['status'] = $status['label'];
            $row['status_class'] = $status['class'];
            $row['rank'] = $status['rank'];
            unset($row['states']);
            $rows[] = $row;
        }

        usort($rows, function ($a, $b) {
            if ($a['rank'] !== $b['rank']) {
                return $a['rank'] < $b['rank'] ? -1 : 1;
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
        $menu = [];
        foreach ($bills as $bill) {
            $due += $bill['due'];
            foreach ($bill['menu'] as $line) {
                $menu[] = $line;
            }
        }
        $status = self::orderStatus($menu);
        usort($bills, function ($a, $b) {
            return strcmp($b['when'], $a['when']);
        });

        return [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'due' => $due,
            'status' => $status['label'],
            'status_class' => $status['class'],
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
                    ->orWhereHas('items', function ($items) {
                        $items->whereNotIn('status', ['delivered', 'cancelled', 'rejected']);
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
                if ((string) $item->status === 'cancelled') {
                    continue;
                }
                $names[] = trim($item->product_name.' × '.($item->qty + 0));
                $menu[] = self::menuLine($item);
            }
            if (count($menu) < 1) {
                continue;
            }
            $bills[$sale->id] = [
                'sale_id' => $sale->id,
                'reference' => $sale->reference_no,
                'customer' => optional($sale->customer)->name ?: '',
                'phone' => optional($sale->customer)->phone_number ?: '',
                'total' => (float) $sale->grand_total,
                'paid' => (float) $sale->paid_amount,
                'due' => $due,
                'status' => self::paymentLabel($sale, $due),
                'when' => $when,
                'when_label' => $sale->created_at ? $sale->created_at->format('j M Y H:i') : '',
                'today' => substr($when, 0, 10) === $today,
                'items' => $names,
                'menu' => $menu,
                'order_status' => self::orderStatus($menu)['label'],
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
        $clock = '';
        if ($state === 'preparing') {
            $label = 'Accepted';
            $clock = $dueAt ? 'down' : '';
        } elseif ($state === 'ready') {
            $label = 'Ready for delivery';
            $clock = $readyAt ? 'up' : '';
        } elseif ($state === 'delivered') {
            $label = 'Delivered';
        } elseif ($state === 'rejected') {
            $label = 'Rejected';
        } elseif ($state === 'hold') {
            $label = 'On hold';
        } else {
            $label = 'Waiting';
            $state = $state === '' ? 'awaiting' : $state;
        }

        return [
            'kind' => $item->department === 'drinks' ? 'Drink' : 'Food',
            'name' => $item->product_name,
            'qty' => $item->qty + 0,
            'note' => trim((string) $item->customer_comment),
            'state' => $state,
            'label' => $label,
            'clock' => $clock,
            'due_at' => $dueAt,
            'ready_at' => $readyAt,
        ];
    }

    protected static function paymentLabel($sale, $due)
    {
        if ($due <= 0.009 || (int) $sale->payment_status === 4) {
            return 'Paid';
        }
        if ((int) $sale->payment_status === 1) {
            return 'Pending';
        }

        return 'Due';
    }

    protected static function orderStatus(array $menu)
    {
        $rank = [
            'ready' => ['Ready for delivery', 'ready', 0],
            'awaiting' => ['Waiting', 'waiting', 1],
            'preparing' => ['Accepted', 'accepted', 2],
            'hold' => ['On hold', 'hold', 3],
            'delivered' => ['Delivered', 'delivered', 4],
            'rejected' => ['Rejected', 'rejected', 5],
        ];
        $best = null;
        foreach ($menu as $line) {
            $state = isset($line['state']) ? (string) $line['state'] : '';
            if (! isset($rank[$state])) {
                continue;
            }
            if ($best === null || $rank[$state][2] < $best[2]) {
                $best = $rank[$state];
            }
        }

        return [
            'label' => $best ? $best[0] : 'Waiting',
            'class' => $best ? $best[1] : 'waiting',
            'rank' => $best ? $best[2] : 1,
        ];
    }

    protected static function rollupStatus(array $statuses)
    {
        if (in_array('Due', $statuses, true)) {
            return 'Due';
        }
        if (in_array('Pending', $statuses, true)) {
            return 'Pending';
        }

        return 'Paid';
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
