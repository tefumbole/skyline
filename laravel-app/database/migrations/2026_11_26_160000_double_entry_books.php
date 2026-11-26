<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DoubleEntryBooks extends Migration
{
    public function up()
    {
        if (Schema::hasTable('expenses') && ! Schema::hasColumn('expenses', 'credit_account_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->unsignedInteger('credit_account_id')->nullable()->after('account_id');
            });
        }

        $chart = app(\App\Support\ChartOfAccounts::class);
        $chart->ensure();
        $chart->syncBooks();
    }

    public function down()
    {
        if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'credit_account_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropColumn('credit_account_id');
            });
        }
    }
}
