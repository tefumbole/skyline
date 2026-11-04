<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class GrantSuperAdminEveryPermission extends Migration
{
    public function up()
    {
        $roleId = (int) DB::table('roles')->where('name', 'Super Admin')->value('id');
        if (! $roleId) {
            return;
        }

        $have = array_flip(DB::table('role_has_permissions')->where('role_id', $roleId)->pluck('permission_id')->all());
        foreach (DB::table('permissions')->pluck('id') as $permissionId) {
            if (isset($have[$permissionId])) {
                continue;
            }
            DB::table('role_has_permissions')->insert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }

        $userId = DB::table('users')->whereRaw('LOWER(username) = ?', ['admin'])->orderBy('id')->value('id');
        if ($userId) {
            DB::table('users')->where('id', $userId)->update([
                'role_id' => $roleId,
                'is_active' => 1,
                'is_deleted' => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
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
