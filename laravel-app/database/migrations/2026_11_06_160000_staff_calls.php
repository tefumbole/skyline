<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StaffCalls extends Migration
{
    public function up()
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'last_seen_at')) {
                    $table->timestamp('last_seen_at')->nullable()->index();
                }
                if (! Schema::hasColumn('users', 'last_seen_ip')) {
                    $table->string('last_seen_ip', 45)->nullable();
                }
            });
        }

        if (! Schema::hasTable('staff_calls')) {
            Schema::create('staff_calls', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('caller_user_id');
                $table->unsignedInteger('callee_user_id');
                $table->string('mode', 10);
                $table->string('status', 20);
                $table->text('offer')->nullable();
                $table->text('answer')->nullable();
                $table->timestamp('touched_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->timestamps();
                $table->index(['callee_user_id', 'status']);
                $table->index(['caller_user_id', 'status']);
            });
        }

        if (! Schema::hasTable('staff_call_signals')) {
            Schema::create('staff_call_signals', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('staff_call_id');
                $table->unsignedInteger('from_user_id');
                $table->text('payload');
                $table->timestamp('created_at')->nullable();
                $table->index(['staff_call_id', 'id']);
            });
        }
    }

    public function down()
    {
    }
}
