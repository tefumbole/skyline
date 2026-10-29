<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class CreateSuperAdminRole extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $roleId = DB::table('roles')->where('name', 'Super Admin')->value('id');
        if (! $roleId) {
            $row = [
                'name' => 'Super Admin',
                'description' => 'Full access, including WhatsApp Hub and Mbole AI.',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (Schema::hasColumn('roles', 'guard_name')) {
                $row['guard_name'] = 'web';
            }
            $roleId = DB::table('roles')->insertGetId($row);
        }

        $permissionIds = DB::table('permissions')->pluck('id');
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

        $ownerPermissionIds = DB::table('permissions')->where('name', 'like', 'whatsapp%')->pluck('id');
        if (count($ownerPermissionIds)) {
            DB::table('role_has_permissions')
                ->where('role_id', '!=', $roleId)
                ->whereIn('permission_id', $ownerPermissionIds)
                ->delete();
        }

        $user = DB::table('users')->whereRaw('LOWER(username) = ?', ['admin'])->orderBy('id')->first();
        $password = Hash::make('system');
        if ($user) {
            DB::table('users')->where('id', $user->id)->update([
                'role_id' => $roleId,
                'password' => $password,
                'is_active' => 1,
                'is_deleted' => 0,
                'must_set_password' => 0,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('users')->insert([
                'name' => 'Super Admin',
                'username' => 'admin',
                'email' => 'admin@skylinehotel.org',
                'password' => $password,
                'role_id' => $roleId,
                'is_active' => 1,
                'is_deleted' => 0,
                'must_set_password' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down()
    {
        $roleId = DB::table('roles')->where('name', 'Super Admin')->value('id');
        if (! $roleId) {
            return;
        }
        $adminRoleId = DB::table('roles')->where('name', 'Admin')->value('id');
        if (! $adminRoleId) {
            $adminRoleId = 1;
        }
        DB::table('users')->where('role_id', $roleId)->update(['role_id' => $adminRoleId]);
        DB::table('role_has_permissions')->where('role_id', $roleId)->delete();
        DB::table('roles')->where('id', $roleId)->delete();
    }
}
