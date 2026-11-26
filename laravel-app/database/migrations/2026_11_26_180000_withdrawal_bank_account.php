<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WithdrawalBankAccount extends Migration
{
    public function up()
    {
        if (Schema::hasTable('campay_payouts') && ! Schema::hasColumn('campay_payouts', 'account_id')) {
            Schema::table('campay_payouts', function (Blueprint $table) {
                $table->unsignedInteger('account_id')->nullable()->after('request_id');
            });
        }

        app(\App\Support\ChartOfAccounts::class)->reclassWithdrawalsToBank();
    }

    public function down()
    {
        if (Schema::hasTable('campay_payouts') && Schema::hasColumn('campay_payouts', 'account_id')) {
            Schema::table('campay_payouts', function (Blueprint $table) {
                $table->dropColumn('account_id');
            });
        }
    }
}
