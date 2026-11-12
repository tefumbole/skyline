<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCashierAtToRestaurantOrders extends Migration
{
    public function up()
    {
        if (Schema::hasTable('restaurant_orders') && ! Schema::hasColumn('restaurant_orders', 'cashier_at')) {
            Schema::table('restaurant_orders', function (Blueprint $table) {
                $table->timestamp('cashier_at')->nullable();
            });
        }
    }

    public function down()
    {
    }
}
