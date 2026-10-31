<?php

use App\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ManagerRoleAndPayoutLock extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions')) {
            return;
        }

        $managerId = $this->role('Manager', 'Dashboard, sales, orders, payments, and reports. Can see payouts but cannot send one.');
        $this->grant($managerId, [
            'sales-index', 'sales-add',
            'payments-index',
            'orders-index',
            'quotes-index',
            'products-index', 'products-add',
            'announcements_module',
            'letter_module', 'letter_index', 'letter_create',
            'profit-loss', 'best-seller', 'daily-sale', 'monthly-sale', 'sale-report', 'payment-report', 'product-report',
            'tasks_module', 'tasks.view', 'tasks.create',
            'timesheets_module', 'timesheets.employee', 'timesheets.admin', 'timesheets.manage',
        ]);

        $accountantId = (int) DB::table('roles')->where('name', 'Accountant')->value('id');
        if ($accountantId) {
            $this->grant($accountantId, ['payments-index', 'one_time_otp']);
            $this->revoke($accountantId, ['sales-index', 'timesheets_module', 'timesheets.employee', 'payments-payout']);
        }

        $payoutId = Permission::firstOrCreate(['name' => 'payments-payout', 'guard_name' => 'web'])->id;
        $paymentsId = DB::table('permissions')->where('name', 'payments-index')->value('id');
        if ($paymentsId) {
            $roleIds = DB::table('role_has_permissions')->where('permission_id', $paymentsId)->pluck('role_id');
            foreach ($roleIds as $roleId) {
                $name = (string) DB::table('roles')->where('id', $roleId)->value('name');
                if (in_array($name, ['Accountant', 'Manager'], true)) {
                    DB::table('role_has_permissions')->where('role_id', $roleId)->where('permission_id', $payoutId)->delete();
                    continue;
                }
                $this->grant((int) $roleId, ['payments-payout']);
            }
        }

        $this->assign('empo@skylinehotel.org', 'Empo Mildred', $accountantId);
        $this->assign('george@skylinehotel.org', 'George Bennette', $managerId);

        try {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
        }
    }

    public function down()
    {
    }

    protected function role($name, $description)
    {
        $now = date('Y-m-d H:i:s');
        $existing = DB::table('roles')->where('name', $name)->first();
        if ($existing) {
            DB::table('roles')->where('id', $existing->id)->update([
                'description' => $description,
                'is_active' => 1,
                'updated_at' => $now,
            ]);

            return (int) $existing->id;
        }
        $row = [
            'name' => $name,
            'description' => $description,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        if (Schema::hasColumn('roles', 'guard_name')) {
            $row['guard_name'] = 'web';
        }

        return (int) DB::table('roles')->insertGetId($row);
    }

    protected function grant($roleId, array $names)
    {
        if (! $roleId) {
            return;
        }
        $role = Role::find($roleId);
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            if (! $role) {
                continue;
            }
            try {
                if (! $role->hasPermissionTo($name)) {
                    $role->givePermissionTo($name);
                }
            } catch (\Throwable $e) {
            }
        }
    }

    protected function revoke($roleId, array $names)
    {
        $role = Role::find($roleId);
        if (! $role) {
            return;
        }
        foreach ($names as $name) {
            try {
                if ($role->hasPermissionTo($name)) {
                    $role->revokePermissionTo($name);
                }
            } catch (\Throwable $e) {
            }
        }
    }

    protected function assign($email, $name, $roleId)
    {
        if (! $roleId) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $user = DB::table('users')->whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
        if (! $user) {
            return;
        }
        DB::table('users')->where('id', $user->id)->update([
            'name' => $name,
            'username' => $name,
            'role_id' => $roleId,
            'password' => Hash::make('system'),
            'company_name' => 'Pure Heritage',
            'must_set_password' => 0,
            'is_active' => 1,
            'is_deleted' => 0,
            'updated_at' => $now,
        ]);
        if (Schema::hasTable('model_has_roles')) {
            DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', $user->id)->delete();
            DB::table('model_has_roles')->insert([
                'role_id' => $roleId,
                'model_type' => User::class,
                'model_id' => $user->id,
            ]);
        }
    }
}
