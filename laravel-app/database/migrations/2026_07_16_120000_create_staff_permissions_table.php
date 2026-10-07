<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateStaffPermissionsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('staff_permissions')) {
            Schema::create('staff_permissions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('user_id')->nullable()->index();
                $table->string('full_name');
                $table->string('email')->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('company_role');
                $table->dateTime('from_at');
                $table->dateTime('to_at');
                $table->text('reason')->nullable();
                $table->string('status', 32)->default('pending')->index();
                $table->text('admin_note')->nullable();
                $table->unsignedInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->string('reference_number', 40)->unique();
                $table->timestamps();
            });
        }

        $perms = [
            'permissions_module',
            'permissions.view',
            'permissions.manage',
        ];
        foreach ($perms as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (Role::whereIn('id', [1, 2])->get() as $admin) {
            foreach ($perms as $name) {
                try {
                    if (! $admin->hasPermissionTo($name)) {
                        $admin->givePermissionTo($name);
                    }
                } catch (\Throwable $e) {
                    // ignore missing permission cache issues during migrate
                }
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('staff_permissions');
    }
}
