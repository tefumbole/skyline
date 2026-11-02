<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GrantManagerPayout extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $permission = DB::table('permissions')->where('name', 'payments-payout')->where('guard_name', 'web')->first();
        if (! $permission) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'payments-payout',
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $permissionId = (int) $permission->id;
        }

        $role = DB::table('roles')->where('name', 'Manager')->first();
        if ($role) {
            $has = DB::table('role_has_permissions')
                ->where('role_id', $role->id)
                ->where('permission_id', $permissionId)
                ->exists();
            if (! $has) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => $role->id,
                ]);
            }
            DB::table('roles')->where('id', $role->id)->update([
                'description' => 'Dashboard, sales, orders, payments, and reports. Can request a payout and approve one.',
                'updated_at' => $now,
            ]);
        }

        if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down()
    {
    }
}
