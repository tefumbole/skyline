<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCothReservations extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('coth_settings')) {
            Schema::create('coth_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedSmallInteger('deposit_percent')->default(20);
                $table->unsignedSmallInteger('discount_percent')->default(20);
                $table->unsignedSmallInteger('hold_minutes')->default(15);
                $table->text('discount_dates');
                $table->string('timezone', 64)->default('Africa/Douala');
                $table->string('event_id', 64)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('coth_sections')) {
            Schema::create('coth_sections', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 20)->unique();
                $table->string('name');
                $table->unsignedSmallInteger('sort')->default(0);
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('coth_tables')) {
            Schema::create('coth_tables', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('coth_section_id');
                $table->string('code', 32)->unique();
                $table->string('name');
                $table->unsignedSmallInteger('capacity');
                $table->string('seating_style', 20)->default('standard');
                $table->unsignedInteger('price');
                $table->boolean('is_active')->default(1);
                $table->decimal('pos_x', 6, 2)->nullable();
                $table->decimal('pos_y', 6, 2)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('coth_slots')) {
            Schema::create('coth_slots', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('coth_table_id');
                $table->date('event_date');
                $table->string('status', 20)->default('open');
                $table->boolean('is_enabled')->default(1);
                $table->timestamps();
                $table->unique(['coth_table_id', 'event_date']);
            });
        }

        if (! Schema::hasTable('coth_reservations')) {
            Schema::create('coth_reservations', function (Blueprint $table) {
                $table->increments('id');
                $table->string('reference', 32)->unique();
                $table->string('public_token', 64)->unique();
                $table->unsignedInteger('coth_slot_id');
                $table->unsignedInteger('coth_table_id');
                $table->date('event_date');
                $table->unsignedInteger('customer_id')->nullable();
                $table->string('customer_name');
                $table->string('phone', 32);
                $table->string('email')->nullable();
                $table->unsignedInteger('list_price');
                $table->unsignedInteger('discount_amount');
                $table->unsignedInteger('final_price');
                $table->unsignedInteger('deposit_due');
                $table->string('pay_choice', 20)->default('deposit');
                $table->unsignedInteger('amount_due');
                $table->unsignedInteger('amount_paid')->default(0);
                $table->integer('balance')->default(0);
                $table->string('status', 32)->default('held');
                $table->string('payment_status', 32)->default('unpaid');
                $table->dateTime('hold_expires_at')->nullable();
                $table->dateTime('phone_verified_at')->nullable();
                $table->dateTime('terms_accepted_at')->nullable();
                $table->unsignedInteger('sale_id')->nullable();
                $table->string('gateway_reference')->nullable();
                $table->string('stripe_session_id')->nullable();
                $table->string('pay_method', 20)->nullable();
                $table->text('staff_note')->nullable();
                $table->timestamps();
                $table->index(['event_date', 'status']);
            });
        }

        if (! Schema::hasTable('coth_audits')) {
            Schema::create('coth_audits', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('actor_user_id')->nullable();
                $table->string('action', 64);
                $table->string('subject_type', 64)->nullable();
                $table->string('subject_id', 64)->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        app(\App\Services\Coth\CothCatalog::class)->seed();

        if (Schema::hasTable('permissions') && class_exists(\Spatie\Permission\Models\Permission::class)) {
            $permission = \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => 'coth-reservations',
                'guard_name' => 'web',
            ]);
            if (Schema::hasTable('roles')) {
                foreach ([1, 4] as $roleId) {
                    $role = \Spatie\Permission\Models\Role::find($roleId);
                    if ($role && ! $role->hasPermissionTo('coth-reservations')) {
                        $role->givePermissionTo($permission);
                    }
                }
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('coth_audits');
        Schema::dropIfExists('coth_reservations');
        Schema::dropIfExists('coth_slots');
        Schema::dropIfExists('coth_tables');
        Schema::dropIfExists('coth_sections');
        Schema::dropIfExists('coth_settings');
    }
}
