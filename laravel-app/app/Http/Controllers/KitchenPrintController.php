<?php

namespace App\Http\Controllers;

use App\GeneralSetting;
use App\PosSetting;
use App\RestaurantOrder;
use App\Sale;

class KitchenPrintController extends Controller
{
    public function show($id)
    {
        $sale = Sale::with(['user', 'products.product'])->findOrFail($id);
        $order = RestaurantOrder::with(['items.productSale', 'diningTable', 'placedBy'])
            ->where('sale_id', $sale->id)
            ->first();
        $priced = [];
        foreach ($sale->products as $row) {
            if ($row->product_id) {
                $priced[$row->product_id] = $row;
            }
        }

        $table = 'Take Away';
        if ($order && $order->service_type === 'dine_in') {
            $name = $order->diningTable ? trim((string) $order->diningTable->name) : '';
            if ($name === '') {
                $table = 'Dine In';
            } elseif (stripos($name, 'table') === false) {
                $table = 'Table '.$name;
            } else {
                $table = $name;
            }
        }

        $placed = 'Staff';
        if ($order && $order->placedBy && $order->placedBy->name) {
            $placed = $order->placedBy->name;
        } elseif ($sale->user && $sale->user->name) {
            $placed = $sale->user->name;
        }

        $lines = [];
        $comments = [];
        if ($order && trim((string) $order->customer_comment) !== '') {
            $comments[] = trim((string) $order->customer_comment);
        }
        if ($order) {
            foreach ($order->items as $item) {
                if (in_array($item->status, ['cancelled', 'rejected'], true)) {
                    continue;
                }
                $kind = $item->department === 'drinks' ? 'Drink' : 'Food';
                $note = trim((string) $item->customer_comment);
                $row = $item->productSale;
                if (! $row && $item->product_id && isset($priced[$item->product_id])) {
                    $row = $priced[$item->product_id];
                }
                $lines[] = $this->kitchenLine($kind, $item->product_name, $item->qty, $note, $row);
            }
        }
        if (! $lines) {
            foreach ($sale->products as $line) {
                $product = $line->product;
                $lines[] = $this->kitchenLine('Order', $product ? $product->name : 'Item', $line->qty, '', $line);
            }
        }

        $setting = PosSetting::query()->orderByDesc('id')->first();
        $general = GeneralSetting::query()->first();

        return view('restaurant.kitchen_print', [
            'sale' => $sale,
            'table' => $table,
            'placed' => $placed,
            'lines' => $lines,
            'comments' => $comments,
            'paper' => $setting && (int) $setting->sunmi_paper_mm === 58 ? 58 : 80,
            'hotel' => $general && $general->site_title ? $general->site_title : 'Skyline Hotel',
            'back' => $this->backUrl(),
        ]);
    }

    protected function kitchenLine($kind, $name, $qty, $note, $row)
    {
        $qty = (float) $qty;
        $amount = $row ? (float) $row->total : 0;
        $price = 0;
        if ($qty > 0 && $amount > 0) {
            $price = $amount / $qty;
        } elseif ($row) {
            $price = (float) $row->net_unit_price;
            $amount = $price * $qty;
        }

        return [
            'kind' => $kind,
            'name' => $name,
            'qty' => $qty + 0,
            'note' => $note,
            'price' => $price,
            'amount' => $amount,
        ];
    }

    protected function backUrl()
    {
        $previous = url()->previous();
        if ($previous && stripos($previous, 'kitchen-print') === false) {
            return $previous;
        }

        return url('pos');
    }
}
