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

        return in_array(strtolower((string) $user->roleName()), ['accountant', 'manager'], true);
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
                    'bills' => 0,
                    'open' => 0,
                    'latest' => $bill['when'],
                    'today' => false,
                ];
            }
            $grouped[$id]['bills']++;
            $grouped[$id]['due'] += $bill['due'];
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
            if ($row['open'] < 1 && ! $row['today']) {
                continue;
            }
            $row['status'] = $row['due'] > 0.009 ? 'Pending' : 'Paid';
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
        $orders = RestaurantOrder::with(['sale.customer', 'diningTable', 'items'])
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
            foreach ($order->items as $item) {
                if (in_array($item->status, ['cancelled', 'rejected'], true)) {
                    continue;
                }
                $names[] = trim($item->product_name.' × '.($item->qty + 0));
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
                'table_id' => $table->id,
                'table_name' => $table->name,
                'table_description' => $table->description ?: '',
            ];
        }

        return array_values($bills);
    }
}
