<?php

namespace App\Services\Restaurant;

use App\Product_Sale;
use App\RestaurantOrder;
use App\Sale;
use Illuminate\Support\Collection;

class RestaurantSettlement
{
    public static function attach(Collection $sales)
    {
        $ids = $sales->pluck('id')->filter()->all();
        $orders = $ids
            ? RestaurantOrder::with('items')->whereIn('sale_id', $ids)->get()->keyBy('sale_id')
            : collect();
        $lines = $ids
            ? Product_Sale::whereIn('sale_id', $ids)->get()->groupBy('sale_id')
            : collect();
        foreach ($sales as $sale) {
            $sale->settlement = self::compute(
                $sale,
                $orders->get($sale->id),
                $lines->get($sale->id) ?: collect()
            );
        }

        return $sales;
    }

    public static function compute(Sale $sale, $order, $lines)
    {
        $paid = (float) $sale->paid_amount;
        $grand = (float) $sale->grand_total;
        $plainDue = max(0, round($grand - $paid, 2));
        if (! $order || ! $order->items || $order->items->isEmpty()) {
            return [
                'queued' => false,
                'pending_delivery' => false,
                'billable' => $grand,
                'due' => $plainDue,
                'pending_amount' => 0,
                'undelivered' => [],
                'badge' => ((int) $sale->payment_status === 1) ? 'Pending' : ($plainDue <= 0.009 ? 'Paid' : 'Due'),
            ];
        }

        $linesById = collect($lines)->keyBy('id');
        $undelivered = [];
        $pendingAmount = 0;
        foreach ($order->items as $item) {
            if (in_array($item->status, ['rejected', 'cancelled', 'delivered'], true)) {
                continue;
            }
            $line = $item->product_sale_id ? $linesById->get($item->product_sale_id) : null;
            $amount = $line ? (float) $line->total : 0;
            $pendingAmount += $amount;
            $minutes = null;
            if ($item->expected_ready_at) {
                $minutes = now()->diffInMinutes($item->expected_ready_at, false);
            }
            $undelivered[] = [
                'name' => $item->product_name,
                'qty' => $item->qty,
                'amount' => $amount,
                'status' => $item->status,
                'minutes_left' => $minutes,
            ];
        }

        return [
            'queued' => true,
            'pending_delivery' => count($undelivered) > 0,
            'billable' => $grand,
            'due' => $plainDue,
            'pending_amount' => round($pendingAmount, 2),
            'undelivered' => $undelivered,
            'badge' => $plainDue <= 0.009 ? 'Paid' : (((int) $sale->payment_status === 1) ? 'Pending' : 'Due'),
        ];
    }
}
