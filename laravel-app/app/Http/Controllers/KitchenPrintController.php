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
        $order = RestaurantOrder::with(['items', 'diningTable', 'placedBy'])
            ->where('sale_id', $sale->id)
            ->first();

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
                $lines[] = [
                    'kind' => $kind,
                    'name' => $item->product_name,
                    'qty' => $item->qty + 0,
                    'note' => $note,
                ];
            }
        }
        if (! $lines) {
            foreach ($sale->products as $line) {
                $product = $line->product;
                $lines[] = [
                    'kind' => 'Order',
                    'name' => $product ? $product->name : 'Item',
                    'qty' => $line->qty + 0,
                    'note' => '',
                ];
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
        ]);
    }
}
