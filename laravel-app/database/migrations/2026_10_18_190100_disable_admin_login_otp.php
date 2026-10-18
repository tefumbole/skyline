<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DisableAdminLoginOtp extends Migration
{
    public function up()
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'otp_disabled')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('otp_disabled')->default(0);
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'otp_disabled')) {
            DB::table('users')->where('username', 'admin')->update([
                'otp_disabled' => 1,
                'otp_verify' => 1,
                'otp' => null,
                'otp_time' => null,
            ]);
        }
    }

    public function down()
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'otp_disabled')) {
            DB::table('users')->where('username', 'admin')->update(['otp_disabled' => 0]);
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('otp_disabled');
            });
        }
    }
}
