<?php

namespace App\Support;

use App\RestaurantOrderItem;
use App\Services\Restaurant\RestaurantAccess;
use Illuminate\Support\Facades\DB;

class RoleDashboard
{
    public static function forUser($user)
    {
        if (! $user || $user->hasFullAdminAccess()) {
            return null;
        }
        $name = strtolower((string) $user->roleName());
        if ($name === 'bar') {
            return self::laneBoard($user, 'bar', 'Bar');
        }
        if ($name === 'restaurant') {
            return self::laneBoard($user, 'restaurant', 'Restaurant');
        }
        if ($name === 'waiter') {
            return self::waiterBoard($user);
        }

        return null;
    }

    protected static function laneBoard($user, $lane, $title)
    {
        return [
            'title' => $title,
            'lead' => 'Open orders on this station, and what was delivered today.',
            'cards' => [
                self::card('Awaiting acceptance', self::orders($lane, ['awaiting']), route('restaurant.orders', ['lane' => $lane, 'tab' => 'awaiting'])),
                self::card('Accepted', self::orders($lane, ['preparing', 'accepted']), route('restaurant.orders', ['lane' => $lane, 'tab' => 'accepted'])),
                self::card('Delivered today', self::orders($lane, ['delivered'], 'delivered_at'), route('restaurant.orders', ['lane' => $lane, 'tab' => 'delivered'])),
                self::card('Clients served today', self::clients($lane, null, 'delivered_at'), null),
            ],
        ];
    }

    protected static function waiterBoard($user)
    {
        return [
            'title' => 'Waiter',
            'lead' => 'Orders you placed that are still open, and the customers you served today.',
            'cards' => [
                self::card('Orders waiting', self::orders(null, ['awaiting'], null, $user->id), route('restaurant.orders', ['tab' => 'awaiting'])),
                self::card('Ready for delivery', self::orders(null, ['ready'], null, $user->id), route('restaurant.ready')),
                self::card('Customers served today', self::clients(null, $user->id, 'delivered_at'), null),
            ],
        ];
    }

    protected static function card($label, $value, $url)
    {
        return ['label' => $label, 'value' => $value, 'url' => $url];
    }

    protected static function orders($lane, array $statuses, $dayColumn = null, $userId = null)
    {
        $query = self::base($lane, $userId);
        $query->whereIn('restaurant_order_items.status', $statuses);
        if ($dayColumn) {
            $query->whereDate('restaurant_order_items.'.$dayColumn, date('Y-m-d'));
        }

        return self::distinctOrders($query);
    }

    protected static function clients($lane, $userId, $dayColumn)
    {
        $query = self::base($lane, $userId);
        $query->where('restaurant_order_items.status', 'delivered');
        if ($dayColumn) {
            $query->whereDate('restaurant_order_items.'.$dayColumn, date('Y-m-d'));
        }
        $query->join('restaurant_orders as role_orders', 'role_orders.id', '=', 'restaurant_order_items.restaurant_order_id')
            ->join('sales as role_sales', 'role_sales.id', '=', 'role_orders.sale_id')
            ->whereNotNull('role_sales.customer_id');

        try {
            return (int) $query->select(DB::raw('COUNT(DISTINCT role_sales.customer_id) as aggregate'))->value('aggregate');
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected static function base($lane, $userId)
    {
        $query = RestaurantOrderItem::query();
        if ($lane) {
            $query = RestaurantAccess::applyLane($query, $lane);
        }
        if ($userId) {
            $query->whereHas('order', function ($order) use ($userId) {
                $order->where('placed_by_user_id', $userId)->orWhere('waitress_user_id', $userId);
            });
        }

        return $query;
    }

    protected static function distinctOrders($query)
    {
        try {
            return (int) $query->select(DB::raw('COUNT(DISTINCT restaurant_order_items.restaurant_order_id) as aggregate'))->value('aggregate');
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
