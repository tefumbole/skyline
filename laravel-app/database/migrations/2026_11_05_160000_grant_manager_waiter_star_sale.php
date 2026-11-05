<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GrantManagerWaiterStarSale extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $permission = DB::table('permissions')->where('name', 'star_product')->where('guard_name', 'web')->first();
        if (! $permission) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'star_product',
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $permissionId = (int) $permission->id;
        }

        $roles = DB::table('roles')->whereIn('name', ['Manager', 'Waiter'])->get();
        foreach ($roles as $role) {
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
        }

        if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down()
    {
    }
}
