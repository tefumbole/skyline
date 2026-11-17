<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminPermissions
{
    /**
     * Attach every permission to the Admin and Super Admin roles.
     * A new permission changes the count, so the next admin request fills the gap.
     */
    public static function sync($force = false)
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')->pluck('id')->all();
        $signature = count($permissionIds);
        $cacheKey = 'skyline-admin-permissions:'.$signature;
        if (! $force) {
            try {
                if (Cache::get($cacheKey) === 1) {
                    return;
                }
            } catch (\Throwable $e) {
            }
        }

        $roleIds = DB::table('roles')
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['admin'])
                    ->orWhereRaw('LOWER(name) = ?', ['super admin']);
            })
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            $have = array_flip(DB::table('role_has_permissions')->where('role_id', $roleId)->pluck('permission_id')->all());
            $rows = [];
            foreach ($permissionIds as $permissionId) {
                if (isset($have[$permissionId])) {
                    continue;
                }
                $rows[] = [
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ];
            }
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('role_has_permissions')->insert($chunk);
            }
        }

        try {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
        }

        try {
            Cache::forever($cacheKey, 1);
        } catch (\Throwable $e) {
        }
    }
}
