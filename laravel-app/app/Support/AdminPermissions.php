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
        $cacheKey = 'skyline-admin-permissions:v2:'.$signature;
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

        self::grantExpenseActions();
        self::linkEveryUser();

        try {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
        }

        try {
            Cache::forever($cacheKey, 1);
        } catch (\Throwable $e) {
        }
    }

    /**
     * The Expenses tick includes creating and editing an expense.
     */
    protected static function grantExpenseActions()
    {
        $index = DB::table('permissions')->where('name', 'expenses-index')->where('guard_name', 'web')->value('id');
        if (! $index) {
            return;
        }
        $roleIds = DB::table('role_has_permissions')->where('permission_id', $index)->pluck('role_id');
        foreach (['expenses-add', 'expenses-edit', 'expenses-delete'] as $name) {
            $permissionId = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->value('id');
            if (! $permissionId) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            foreach ($roleIds as $roleId) {
                $exists = DB::table('role_has_permissions')
                    ->where('permission_id', $permissionId)
                    ->where('role_id', $roleId)
                    ->exists();
                if ($exists) {
                    continue;
                }
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    /**
     * A user's role on their account is the role whose checkboxes apply to them.
     */
    protected static function linkEveryUser()
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('model_has_roles')) {
            return;
        }
        $users = DB::table('users')->whereNotNull('role_id')->get(['id', 'role_id']);
        foreach ($users as $user) {
            $linked = DB::table('model_has_roles')
                ->where('role_id', $user->role_id)
                ->where('model_id', $user->id)
                ->where('model_type', \App\User::class)
                ->exists();
            if ($linked) {
                continue;
            }
            DB::table('model_has_roles')->insert([
                'role_id' => $user->role_id,
                'model_type' => \App\User::class,
                'model_id' => $user->id,
            ]);
        }
    }
}
