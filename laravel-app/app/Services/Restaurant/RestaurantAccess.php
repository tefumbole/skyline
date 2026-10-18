<?php

namespace App\Services\Restaurant;

use Spatie\Permission\Models\Role;

class RestaurantAccess
{
    public static function can($user, $permission)
    {
        if (! $user) {
            return false;
        }
        if ((int) $user->role_id <= 2) {
            return true;
        }
        try {
            $role = Role::find($user->role_id);
            if (! $role) {
                return false;
            }

            return $role->hasPermissionTo($permission);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function canDepartment($user, $department)
    {
        if (self::can($user, 'order-logs')) {
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
}
