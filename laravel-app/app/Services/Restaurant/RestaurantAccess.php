<?php

namespace App\Services\Restaurant;

use App\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RestaurantAccess
{
    public static function can($user, $permission)
    {
        if (! $user || ! $user->role_id) {
            return false;
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
