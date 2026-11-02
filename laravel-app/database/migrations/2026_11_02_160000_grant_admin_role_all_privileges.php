<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GrantAdminRoleAllPrivileges extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        foreach (['users-index', 'users-add', 'users-edit', 'users-delete'] as $name) {
            $exists = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->exists();
            if ($exists) {
                continue;
            }
            DB::table('permissions')->insert([
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIds = DB::table('permissions')->pluck('id');
        $roleIds = DB::table('roles')->whereIn('name', ['Admin', 'Super Admin'])->pluck('id');
        foreach ($roleIds as $roleId) {
            $already = DB::table('role_has_permissions')->where('role_id', $roleId)->pluck('permission_id')->all();
            $have = array_flip($already);
            foreach ($permissionIds as $permissionId) {
                if (isset($have[$permissionId])) {
                    continue;
                }
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
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
