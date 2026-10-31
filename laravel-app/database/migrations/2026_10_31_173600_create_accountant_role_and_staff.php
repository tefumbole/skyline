<?php

use App\Support\WhatsAppPhone;
use App\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateAccountantRoleAndStaff extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('users')) {
            return;
        }

        $accountantId = $this->role('Accountant', 'Sales, payments, and employee timesheets. Login uses a WhatsApp code.');
        $this->grant($accountantId, [
            'sales-index',
            'payments-index',
            'timesheets_module',
            'timesheets.employee',
            'one_time_otp',
        ]);

        $roles = [
            'Accountant' => $accountantId,
            'Waiter' => $this->roleId('Waiter'),
            'Bar' => $this->roleId('Bar'),
            'Restaurant' => $this->roleId('Restaurant'),
        ];

        $people = [
            ['Empo Mildred', '237670163241', 'Accountant'],
            ['Mbuh Marko', '237676906077', 'Waiter'],
            ['Achu Annette', '237678684627', 'Waiter'],
            ['Awah Gwen', '237650097024', 'Waiter'],
            ['Nkonji Daphine', '237679056879', 'Waiter'],
            ['Acho Vanessa', '237680929268', 'Waiter'],
            ['Foning Kevine', '237683710574', 'Waiter'],
            ['Emmanuella Ngefor', '237681757956', 'Waiter'],
            ['Ndikum Presley', '237681718965', 'Bar'],
            ['Yoye Herman', '237682990789', 'Bar'],
            ['George Bennette', '237681441396', 'Restaurant'],
            ['Chin Emmanuel', '237674149116', 'Restaurant'],
            ['Viyoff Marceline', '237676414897', 'Restaurant'],
            ['Ngenue Edith', '237650729385', 'Restaurant'],
            ['Ngeulah Ernestine', '237670087941', 'Restaurant'],
        ];

        foreach ($people as $person) {
            $roleId = isset($roles[$person[2]]) ? $roles[$person[2]] : null;
            if (! $roleId) {
                continue;
            }
            $this->staff($person[0], $person[1], $roleId);
        }

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

    protected function roleId($name)
    {
        return (int) DB::table('roles')->where('name', $name)->value('id');
    }

    protected function grant($roleId, array $names)
    {
        if (! $roleId || ! Schema::hasTable('permissions') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }
        $role = Role::find($roleId);
        foreach ($names as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            if (! $role) {
                $exists = DB::table('role_has_permissions')->where('role_id', $roleId)->where('permission_id', $permission->id)->exists();
                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permission->id,
                        'role_id' => $roleId,
                    ]);
                }
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

    protected function staff($name, $phone, $roleId)
    {
        $now = date('Y-m-d H:i:s');
        $stored = WhatsAppPhone::sanitizeForStorage($phone);
        $tail = substr(preg_replace('/\D/', '', $stored), -9);
        $first = strtolower(strtok(trim($name), ' '));
        $email = $first.'@skylinehotel.org';
        $username = $first;

        $user = DB::table('users')
            ->where(function ($query) use ($tail, $email) {
                $query->where('phone', 'like', '%'.$tail)
                    ->orWhereRaw('LOWER(email) = ?', [$email]);
            })
            ->orderBy('id')
            ->first();
        if ($user && ((int) $user->id === 1 || strtolower((string) $user->username) === 'admin')) {
            $user = null;
        }

        $row = [
            'name' => $name,
            'email' => $email,
            'phone' => $stored,
            'company_name' => 'Pure Heritage',
            'role_id' => $roleId,
            'is_active' => 1,
            'is_deleted' => 0,
            'updated_at' => $now,
        ];
        if (Schema::hasColumn('users', 'username')) {
            $taken = DB::table('users')->where('username', $username);
            if ($user) {
                $taken->where('id', '!=', $user->id);
            }
            if (! $taken->exists()) {
                $row['username'] = $username;
            }
        }
        if (Schema::hasColumn('users', 'otp_disabled')) {
            $row['otp_disabled'] = 0;
        }
        if (Schema::hasColumn('users', 'must_set_password') && ! $user) {
            $row['must_set_password'] = 1;
        }
        $billerId = Schema::hasTable('billers') ? DB::table('billers')->where('is_active', 1)->value('id') : null;
        $warehouseId = Schema::hasTable('warehouses') ? DB::table('warehouses')->where('is_active', 1)->value('id') : null;
        if ($billerId && Schema::hasColumn('users', 'biller_id')) {
            $row['biller_id'] = $billerId;
        }
        if ($warehouseId && Schema::hasColumn('users', 'warehouse_id')) {
            $row['warehouse_id'] = $warehouseId;
        }

        if ($user) {
            if (DB::table('users')->whereRaw('LOWER(email) = ?', [$email])->where('id', '!=', $user->id)->exists()) {
                unset($row['email']);
            }
            DB::table('users')->where('id', $user->id)->update($row);
            $userId = (int) $user->id;
        } else {
            $row['password'] = Hash::make(bin2hex(random_bytes(12)));
            $row['created_at'] = $now;
            $userId = (int) DB::table('users')->insertGetId($row);
        }

        if (Schema::hasTable('model_has_roles')) {
            DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', $userId)->delete();
            DB::table('model_has_roles')->insert([
                'role_id' => $roleId,
                'model_type' => User::class,
                'model_id' => $userId,
            ]);
        }

        $this->place($userId, $name, $stored, $email);
    }

    protected function place($userId, $name, $phone, $email)
    {
        if (! Schema::hasTable('customers')) {
            return;
        }
        $tail = substr(preg_replace('/\D/', '', $phone), -9);
        $now = date('Y-m-d H:i:s');
        $groupId = Schema::hasTable('customer_groups') ? DB::table('customer_groups')->value('id') : 1;
        $values = [
            'name' => $name,
            'company_name' => 'Pure Heritage',
            'email' => $email,
            'phone_number' => $phone,
            'address' => 'Pure Heritage',
            'city' => 'Bamenda',
            'country' => 'Cameroon',
            'is_active' => 1,
            'updated_at' => $now,
        ];
        if (Schema::hasColumn('customers', 'user_id')) {
            $values['user_id'] = $userId;
        }
        if (Schema::hasColumn('customers', 'cloud_tenant_id')) {
            $values['cloud_tenant_id'] = 1;
        }
        foreach (['points' => 0, 'deposit' => 0, 'expense' => 0, 'credit_limit' => 0] as $column => $value) {
            if (Schema::hasColumn('customers', $column)) {
                $values[$column] = $value;
            }
        }
        $customer = DB::table('customers')->where('phone_number', 'like', '%'.$tail)->orderBy('id')->first();
        if ($customer) {
            DB::table('customers')->where('id', $customer->id)->update($values);

            return;
        }
        $values['customer_group_id'] = $groupId ?: 1;
        $values['created_at'] = $now;
        DB::table('customers')->insert($values);
    }
}
