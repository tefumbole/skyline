<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ExtendCourseManagerTables extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('course_certificates')) {
            Schema::create('course_certificates', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('certificate_number', 100)->unique();
                $table->uuid('registration_id')->nullable()->index();
                $table->uuid('course_id')->nullable()->index();
                $table->string('student_name');
                $table->string('course_name');
                $table->dateTime('completion_date')->nullable();
                $table->string('status', 50)->default('active');
                $table->dateTime('revoked_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('course_invoices')) {
            Schema::create('course_invoices', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('invoice_number', 100)->unique();
                $table->uuid('registration_id')->nullable()->index();
                $table->string('client_name');
                $table->string('email')->nullable();
                $table->longText('courses_json')->nullable();
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('tax', 14, 2)->default(0);
                $table->decimal('total', 14, 2)->default(0);
                $table->string('payment_method', 100)->nullable();
                $table->string('payment_status', 50)->default('pending');
                $table->dateTime('payment_date')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('student_progress') && ! Schema::hasColumn('student_progress', 'last_updated')) {
            Schema::table('student_progress', function (Blueprint $table) {
                $table->dateTime('last_updated')->nullable()->after('completion_date');
            });
        }

        $this->seedPermissions();
    }

    private function seedPermissions()
    {
        $names = [
            'courses_module',
            'courses.view',
            'courses.create',
            'courses.update',
            'courses.delete',
        ];

        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (Role::whereIn('id', [1, 2])->get() as $role) {
            foreach ($names as $name) {
                try {
                    $role->givePermissionTo($name);
                } catch (\Exception $e) {
                    // already assigned
                }
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('course_invoices');
        Schema::dropIfExists('course_certificates');
    }
}
