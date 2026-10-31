<?php

namespace App\Services\Restaurant;

use App\RestaurantOrderItem;
use App\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class RestaurantAccess
{
    protected static $permissionNames = [];

    public static function can($user, $permission)
    {
        if (! $user || ! $user->role_id) {
            return false;
        }
        $names = self::namesForRole($user->role_id);

        return isset($names[$permission]);
    }

    protected static function namesForRole($roleId)
    {
        if (isset(self::$permissionNames[$roleId])) {
            return self::$permissionNames[$roleId];
        }
        $names = [];
        try {
            $role = Role::find($roleId);
            if ($role) {
                foreach ($role->permissions as $permission) {
                    $names[$permission->name] = true;
                }
            }
        } catch (\Throwable $e) {
            $names = [];
        }

        return self::$permissionNames[$roleId] = $names;
    }

    public static function installed()
    {
        static $ready;
        if ($ready !== null) {
            return $ready;
        }
        try {
            $ready = Schema::hasTable('restaurant_order_items');
        } catch (\Throwable $e) {
            $ready = false;
        }

        return $ready;
    }

    public static function usersWithPermission($permission)
    {
        try {
            $permissionId = DB::table('permissions')->where('name', $permission)->value('id');
            if (! $permissionId) {
                return collect();
            }
            $roleIds = DB::table('role_has_permissions')->where('permission_id', $permissionId)->pluck('role_id');
            if ($roleIds->isEmpty()) {
                return collect();
            }

            return User::whereIn('role_id', $roleIds)->where('is_active', 1)->orderBy('name')->get(['id', 'name']);
        } catch (\Throwable $e) {
            return collect();
        }
    }

    public static function canDepartment($user, $department)
    {
        if (self::can($user, 'order-logs') || self::can($user, 'orders-index')) {
            return true;
        }
        if ($department === 'food') {
            return self::can($user, 'kitchen-orders');
        }
        if ($department === 'drinks') {
            return self::can($user, 'bar-orders');
        }

        return false;
    }

    public static function canSeeOrders($user)
    {
        foreach (['kitchen-orders', 'bar-orders', 'waiter-orders', 'order-logs', 'orders-index', 'orders-add', 'orders-edit', 'orders-delete'] as $permission) {
            if (self::can($user, $permission)) {
                return true;
            }
        }
        foreach (array_keys(self::lanes()) as $key) {
            if (self::can($user, 'orders-'.$key.'-index')) {
                return true;
            }
        }

        return false;
    }

    public static function canItem($user, $item)
    {
        if (self::can($user, 'order-logs') || self::can($user, 'orders-index')) {
            return true;
        }
        $lane = $item ? $item->lane : null;
        if ($lane === 'restaurant') {
            return self::can($user, 'kitchen-orders') || self::can($user, 'orders-restaurant-index');
        }
        if ($lane === 'bar' || $lane === 'coffee') {
            return self::can($user, 'bar-orders') || self::can($user, 'orders-bar-index') || self::can($user, 'orders-coffee-index');
        }
        if ($lane === 'online') {
            return self::can($user, 'orders-online-index') || self::canDepartment($user, $item ? $item->department : null);
        }

        return self::canDepartment($user, $item ? $item->department : null);
    }

    public static function permissionRows()
    {
        return [
            ['Orders', 'orders-index', 'orders-add', 'orders-edit', 'orders-delete'],
            ['Restaurant', 'orders-restaurant-index', 'orders-restaurant-add', 'orders-restaurant-edit', 'orders-restaurant-delete'],
            ['Bar', 'orders-bar-index', 'orders-bar-add', 'orders-bar-edit', 'orders-bar-delete'],
            ['Online orders', 'orders-online-index', 'orders-online-add', 'orders-online-edit', 'orders-online-delete'],
            ['Awaiting', 'orders-awaiting-index', 'orders-awaiting-add', 'orders-awaiting-edit', 'orders-awaiting-delete'],
            ['Accepted', 'orders-accepted-index', 'orders-accepted-add', 'orders-accepted-edit', 'orders-accepted-delete'],
            ['Ready for Delivery', 'orders-ready-index', 'orders-ready-add', 'orders-ready-edit', 'orders-ready-delete'],
            ['Delivered', 'orders-delivered-index', 'orders-delivered-add', 'orders-delivered-edit', 'orders-delivered-delete'],
        ];
    }

    public static function lanes()
    {
        return [
            'restaurant' => 'Restaurant',
            'bar' => 'Bar',
            'online' => 'Online orders',
        ];
    }

    public static function statuses()
    {
        return [
            'awaiting' => 'Awaiting',
            'accepted' => 'Accepted',
            'ready' => 'Ready for Delivery',
            'hold' => 'On Hold',
            'delivered' => 'Delivered',
        ];
    }

    public static function helpHashForLane($lane)
    {
        if ($lane === 'online') {
            return 'orders';
        }
        if ($lane === 'coffee') {
            return 'restaurant-coffee';
        }
        if ($lane === 'bar') {
            return 'restaurant-bar';
        }

        return 'restaurant';
    }

    public static function seeLane($user, $lane)
    {
        if (self::can($user, 'orders-index') || self::can($user, 'orders-'.$lane.'-index') || self::can($user, 'order-logs')) {
            return true;
        }
        if ($lane === 'restaurant') {
            return self::can($user, 'kitchen-orders');
        }
        if ($lane === 'bar') {
            return self::can($user, 'bar-orders');
        }

        return false;
    }

    /** Waiters deliver food and drinks once they are ready. Bar and kitchen do not. */
    public static function canReadyDesk($user)
    {
        return self::can($user, 'waiter-orders')
            || self::can($user, 'orders-ready-index')
            || self::can($user, 'orders-ready-edit')
            || self::can($user, 'orders-index');
    }

    /** Ready-for-delivery popups go to waiters, not to the bar or the kitchen. */
    public static function canReadyAlerts($user)
    {
        return self::can($user, 'waiter-orders') || self::can($user, 'orders-ready-index');
    }

    /** Bar or restaurant. Online drinks belong to the bar; online food belongs to the restaurant. */
    public static function itemStation($item)
    {
        $lane = $item && $item->lane ? (string) $item->lane : '';
        $department = $item ? (string) $item->department : '';
        if ($lane === 'bar' || $lane === 'coffee' || $department === 'drinks') {
            return 'bar';
        }

        return 'restaurant';
    }

    public static function seesStation($user, $station)
    {
        if (self::can($user, 'orders-index') || self::can($user, 'order-logs')) {
            return true;
        }
        if ($station === 'bar') {
            return self::can($user, 'bar-orders')
                || self::can($user, 'orders-bar-index')
                || self::can($user, 'orders-coffee-index');
        }

        return self::can($user, 'kitchen-orders') || self::can($user, 'orders-restaurant-index');
    }

    public static function seeStatus($user, $status)
    {
        if (self::can($user, 'orders-index') || self::can($user, 'orders-'.$status.'-index') || self::can($user, 'order-logs')) {
            return true;
        }
        $explicit = false;
        foreach (array_keys(self::statuses()) as $key) {
            if (self::can($user, 'orders-'.$key.'-index')) {
                $explicit = true;
            }
        }
        if ($explicit) {
            return false;
        }

        return self::canSeeOrders($user);
    }

    public static function canDeliver($user, $item)
    {
        if (! $item || $item->status !== 'ready') {
            return false;
        }

        return self::can($user, 'orders-index')
            || self::can($user, 'order-logs')
            || self::can($user, 'orders-ready-edit')
            || self::can($user, 'waiter-orders');
    }

    public static function canOrder($user, $action, $item = null)
    {
        $lane = $item ? $item->lane : null;
        $status = $item ? $item->status : null;
        if ($status === 'preparing') {
            $status = 'accepted';
        }
        if ($status === 'hold' && $item) {
            $status = $item->previous_status === 'preparing' ? 'accepted' : ($item->previous_status ?: 'awaiting');
        }
        $names = ['orders-'.$action];
        if ($lane) {
            $names[] = 'orders-'.$lane.'-'.$action;
        }
        if ($status) {
            $names[] = 'orders-'.$status.'-'.$action;
        }
        foreach ($names as $name) {
            if (self::can($user, $name)) {
                return true;
            }
        }
        if ($action === 'delete') {
            return self::can($user, 'meal-rejections');
        }
        if (($action === 'add' || $action === 'edit') && $item && self::canItem($user, $item)) {
            return true;
        }
        if ($action === 'edit' && $status === 'ready' && self::can($user, 'waiter-orders')) {
            return true;
        }

        return false;
    }

    public static function queueCounts()
    {
        if (! self::installed() || ! Schema::hasColumn('restaurant_order_items', 'lane')) {
            return [];
        }
        try {
            return Cache::remember('skyline.order.counts', 15, function () {
                return self::countQueues();
            });
        } catch (\Throwable $e) {
            return self::countQueues();
        }
    }

    public static function forgetCounts()
    {
        try {
            Cache::forget('skyline.order.counts');
        } catch (\Throwable $e) {
        }
    }

    protected static function countQueues()
    {
        $counts = [];
        foreach (array_keys(self::lanes()) as $lane) {
            $rows = self::applyLane(RestaurantOrderItem::query(), $lane)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get();
            foreach ($rows as $row) {
                $status = $row->status === 'preparing' ? 'accepted' : $row->status;
                if (! isset($counts[$lane][$status])) {
                    $counts[$lane][$status] = 0;
                }
                $counts[$lane][$status] += (int) $row->total;
            }
        }

        return $counts;
    }

    public static function applyLane($query, $lane)
    {
        if ($lane === 'online') {
            return $query->whereHas('order', function ($order) {
                $order->where('source', 'frontend');
            });
        }
        if ($lane === 'restaurant') {
            return $query->where(function ($items) {
                $items->where('lane', 'restaurant')
                    ->orWhere(function ($food) {
                        $food->where('department', 'food')->where(function ($older) {
                            $older->whereNull('lane')->orWhere('lane', 'online');
                        });
                    });
            });
        }
        if ($lane === 'bar') {
            return $query->where(function ($items) {
                $items->whereIn('lane', ['bar', 'coffee'])
                    ->orWhere(function ($drinks) {
                        $drinks->where('department', 'drinks')->where(function ($older) {
                            $older->whereNull('lane')->orWhere('lane', 'online');
                        });
                    });
            });
        }

        return $query->where('lane', $lane);
    }
}
