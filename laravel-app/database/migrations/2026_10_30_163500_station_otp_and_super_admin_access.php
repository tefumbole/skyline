<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class StationOtpAndSuperAdminAccess extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        Permission::firstOrCreate(['name' => 'one_time_otp', 'guard_name' => 'web']);

        foreach (['Restaurant', 'Bar', 'Waiter'] as $name) {
            $role = Role::where('name', $name)->first();
            if (! $role) {
                continue;
            }
            try {
                if (! $role->hasPermissionTo('one_time_otp')) {
                    $role->givePermissionTo('one_time_otp');
                }
            } catch (\Throwable $e) {
            }
        }

        if (Schema::hasColumn('users', 'otp_disabled')) {
            $roleIds = DB::table('roles')->whereIn('name', ['Restaurant', 'Bar', 'Waiter'])->pluck('id');
            if (count($roleIds)) {
                DB::table('users')->whereIn('role_id', $roleIds)->update(['otp_disabled' => 0]);
            }
        }

        $superId = DB::table('roles')->where('name', 'Super Admin')->value('id');
        if ($superId) {
            $already = DB::table('role_has_permissions')->where('role_id', $superId)->pluck('permission_id')->flip();
            foreach (DB::table('permissions')->pluck('id') as $permissionId) {
                if (isset($already[$permissionId])) {
                    continue;
                }
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => $superId,
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
