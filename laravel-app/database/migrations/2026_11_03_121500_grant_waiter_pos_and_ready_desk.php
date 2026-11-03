<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GrantWaiterPosAndReadyDesk extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $names = ['sales-add', 'sales-index', 'waiter-orders', 'orders-ready-index', 'orders-ready-edit'];
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $role = Role::where('name', 'Waiter')->first();
        if (! $role) {
            return;
        }

        foreach ($names as $name) {
            try {
                if (! $role->hasPermissionTo($name)) {
                    $role->givePermissionTo($name);
                }
            } catch (\Throwable $e) {
            }
        }

        DB::table('roles')->where('id', $role->id)->update([
            'description' => 'Place orders on the POS and see when orders are ready for delivery.',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        try {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
        }
    }

    public function down()
    {
    }
}
