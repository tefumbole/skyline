<?php

namespace App\Http\Controllers;

use App\RestaurantOrder;
use Illuminate\Support\Facades\Schema;

class FloorOrderController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user && strcasecmp((string) $user->roleName(), 'Waiter') === 0) {
            return redirect()->route('restaurant.ready');
        }
        $allowed = $user && (
            $user->hasFullAdminAccess()
            || in_array(strtolower((string) $user->roleName()), ['waiter', 'manager'], true)
        );
        if (! $allowed) {
            return redirect('/')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }

        return view('restaurant.floor_orders', [
            'orders' => $this->orders(),
        ]);
    }

    protected function orders()
    {
        if (! Schema::hasTable('restaurant_orders')) {
            return [];
        }
        $today = date('Y-m-d');
        $rows = RestaurantOrder::with(['sale', 'diningTable', 'items'])
            ->where(function ($query) use ($today) {
                $query->whereDate('created_at', $today)
                    ->orWhereHas('items', function ($items) {
                        $items->whereNotIn('status', ['delivered', 'cancelled', 'rejected']);
                    });
            })
            ->orderByDesc('id')
            ->limit(80)
            ->get();

        $orders = [];
        foreach ($rows as $order) {
            $active = $order->items->filter(function ($item) {
                return ! in_array($item->status, ['cancelled', 'rejected'], true);
            });
            if ($active->isEmpty()) {
                continue;
            }
            $ready = $active->where('status', 'ready')->count();
            $delivered = $active->where('status', 'delivered')->count();
            $preparing = $active->whereIn('status', ['preparing', 'hold'])->count();
            if ($delivered === $active->count()) {
                $status = 'Delivered';
            } elseif ($ready > 0) {
                $status = 'Ready for delivery';
            } elseif ($preparing > 0) {
                $status = 'Being prepared';
            } else {
                $status = 'Waiting';
            }
            $lines = [];
            foreach ($active as $item) {
                $lines[] = [
                    'name' => $item->product_name,
                    'qty' => $item->qty + 0,
                    'status' => $this->itemStatus($item->status),
                ];
            }
            $orders[] = [
                'reference' => optional($order->sale)->reference_no ?: ('Order '.$order->id),
                'destination' => $this->destination($order),
                'status' => $status,
                'when' => $order->created_at ? $order->created_at->format('j M Y H:i') : '',
                'lines' => $lines,
            ];
        }

        return $orders;
    }

    protected function destination($order)
    {
        $service = (string) $order->service_type;
        if ($service === 'take_away' || $service === 'takeaway') {
            return 'Take away';
        }
        if ($order->diningTable) {
            return 'Deliver to '.$order->diningTable->name;
        }
        if ($service === 'dine_in') {
            return 'Dine in';
        }

        return 'Take away';
    }

    protected function itemStatus($status)
    {
        if ($status === 'preparing' || $status === 'hold') {
            return 'Being prepared';
        }
        if ($status === 'ready') {
            return 'Ready for delivery';
        }
        if ($status === 'delivered') {
            return 'Delivered';
        }

        return 'Waiting';
    }
}
