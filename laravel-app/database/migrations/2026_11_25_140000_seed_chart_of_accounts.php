<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SeedChartOfAccounts extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('account_entries')) {
            Schema::create('account_entries', function (Blueprint $table) {
                $table->increments('id');
                $table->string('source', 40);
                $table->unsignedInteger('source_id');
                $table->string('account_no', 20);
                $table->decimal('amount', 14, 2);
                $table->string('direction', 8);
                $table->timestamps();
                $table->unique(array('source', 'source_id', 'account_no', 'direction'), 'account_entries_once');
            });
        }

        $chart = app(\App\Support\ChartOfAccounts::class);
        $chart->ensure();
        $chart->backfill();
    }

    public function down()
    {
        Schema::dropIfExists('account_entries');
    }
}
