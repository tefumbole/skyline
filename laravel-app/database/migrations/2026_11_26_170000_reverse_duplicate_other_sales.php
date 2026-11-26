<?php

use Illuminate\Database\Migrations\Migration;

class ReverseDuplicateOtherSales extends Migration
{
    public function up()
    {
        app(\App\Support\ChartOfAccounts::class)->reverseMispostedOtherSales();
    }

    public function down()
    {
    }
}
