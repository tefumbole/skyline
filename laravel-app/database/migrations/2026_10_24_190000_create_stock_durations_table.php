<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockDurationsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('stock_durations')) {
            return;
        }

        Schema::create('stock_durations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id')->index();
            $table->date('out_of_stock')->nullable();
            $table->date('restock')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_durations');
    }
}
