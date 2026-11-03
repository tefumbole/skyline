<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GrantWaiterAndManagerCustomerCreate extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions')) {
            return;
        }

        foreach (['customers-add', 'customers-index'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $this->grant('Waiter', ['customers-add']);
        $this->grant('Manager', ['customers-index', 'customers-add']);

        try {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
        }
    }

    public function down()
    {
    }

    protected function grant($roleName, array $permissions)
    {
        $role = Role::where('name', $roleName)->first();
        if (! $role) {
            return;
        }
        foreach ($permissions as $name) {
            try {
                if (! $role->hasPermissionTo($name)) {
                    $role->givePermissionTo($name);
                }
            } catch (\Throwable $e) {
            }
        }
    }
}
