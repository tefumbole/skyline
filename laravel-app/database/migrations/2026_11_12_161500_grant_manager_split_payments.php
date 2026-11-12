<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GrantManagerSplitPayments extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions')) {
            return;
        }

        $names = [
            'payments-index',
            'payments-add',
            'payments-edit',
            'sales-index',
            'sales-add',
            'sales-edit',
            'tables-index',
            'tables-pay',
            'meal-rejections',
        ];
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $role = Role::where('name', 'Manager')->first();
        if ($role) {
            foreach ($names as $name) {
                try {
                    if (! $role->hasPermissionTo($name)) {
                        $role->givePermissionTo($name);
                    }
                } catch (\Throwable $e) {
                }
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
