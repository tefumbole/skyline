<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedDefaultCashAccount extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('accounts') || DB::table('accounts')->count() > 0) {
            return;
        }

        DB::table('accounts')->insert([
            'account_no' => 'CASH-001',
            'name' => 'Cash',
            'initial_balance' => 0,
            'total_balance' => 0,
            'is_default' => 1,
            'is_active' => 1,
            'is_default_debit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }
        DB::table('accounts')->where('account_no', 'CASH-001')->where('name', 'Cash')->delete();
    }
}
