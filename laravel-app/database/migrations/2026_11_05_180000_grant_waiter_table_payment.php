<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GrantWaiterTablePayment extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $permission = DB::table('permissions')->where('name', 'tables-pay')->where('guard_name', 'web')->first();
        if (! $permission) {
            return;
        }

        $roles = DB::table('roles')->where('name', 'Waiter')->get();
        foreach ($roles as $role) {
            $has = DB::table('role_has_permissions')
                ->where('role_id', $role->id)
                ->where('permission_id', $permission->id)
                ->exists();
            if (! $has) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permission->id,
                    'role_id' => $role->id,
                ]);
            }
        }

        try {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
        }
    }

    public function down()
    {
    }
}
