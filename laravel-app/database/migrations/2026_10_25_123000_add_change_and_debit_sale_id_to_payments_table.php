<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChangeAndDebitSaleIdToPaymentsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'change')) {
                $table->double('change')->default(0);
            }
            if (! Schema::hasColumn('payments', 'debit_sale_id')) {
                $table->integer('debit_sale_id')->nullable();
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            foreach (['debit_sale_id', 'change'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
