<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateRestaurantStaffRoles extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions')) {
            return;
        }

        $names = [
            'kitchen-orders', 'bar-orders', 'waiter-orders',
            'orders-ready-index', 'orders-ready-edit',
            'timesheets_module', 'timesheets.employee',
            'sales-index', 'sales-add',
        ];
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $restaurant = $this->role(
            'Restaurant',
            'Kitchen',
            'Food orders only. Accept with a preparation time, update a delay, mark ready, and fill a timesheet.'
        );
        $bar = $this->role(
            'Bar',
            'Bartender',
            'Drink orders only. Accept with a preparation time, update a delay, mark ready, and fill a timesheet.'
        );
        $waiter = $this->role(
            'Waiter',
            null,
            'Create orders, see when food and drinks are ready, deliver them, and fill a timesheet.'
        );

        $this->grant($restaurant, ['kitchen-orders', 'timesheets_module', 'timesheets.employee']);
        $this->grant($bar, ['bar-orders', 'timesheets_module', 'timesheets.employee']);
        $this->grant($waiter, [
            'waiter-orders', 'orders-ready-index', 'orders-ready-edit',
            'sales-index', 'sales-add', 'timesheets_module', 'timesheets.employee',
        ]);

        $queueIds = Permission::whereIn('name', ['kitchen-orders', 'bar-orders', 'waiter-orders'])->pluck('id');
        if ($queueIds->count()) {
            $roleIds = DB::table('role_has_permissions')->whereIn('permission_id', $queueIds)->pluck('role_id')->unique();
            foreach ($roleIds as $roleId) {
                $this->grant(Role::find($roleId), ['timesheets_module', 'timesheets.employee']);
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

    protected function role($name, $legacy, $description)
    {
        $existing = DB::table('roles')->where('name', $name)->first();
        if ($existing) {
            DB::table('roles')->where('id', $existing->id)->update([
                'description' => $description,
                'is_active' => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return Role::find($existing->id);
        }
        if ($legacy) {
            $old = DB::table('roles')->where('name', $legacy)->first();
            if ($old) {
                DB::table('roles')->where('id', $old->id)->update([
                    'name' => $name,
                    'description' => $description,
                    'is_active' => 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                return Role::find($old->id);
            }
        }
        $row = [
            'name' => $name,
            'description' => $description,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (Schema::hasColumn('roles', 'guard_name')) {
            $row['guard_name'] = 'web';
        }

        return Role::find(DB::table('roles')->insertGetId($row));
    }

    protected function grant($role, array $names)
    {
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
    }
}
