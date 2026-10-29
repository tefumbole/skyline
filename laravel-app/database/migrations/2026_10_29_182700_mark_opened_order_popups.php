<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MarkOpenedOrderPopups extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('restaurant_order_items')) {
            return;
        }
        Schema::table('restaurant_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('restaurant_order_items', 'popup_opened_at')) {
                $table->timestamp('popup_opened_at')->nullable();
            }
            if (! Schema::hasColumn('restaurant_order_items', 'popup_opened_by')) {
                $table->unsignedInteger('popup_opened_by')->nullable();
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('restaurant_order_items')) {
            return;
        }
        Schema::table('restaurant_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('restaurant_order_items', 'popup_opened_by')) {
                $table->dropColumn('popup_opened_by');
            }
            if (Schema::hasColumn('restaurant_order_items', 'popup_opened_at')) {
                $table->dropColumn('popup_opened_at');
            }
        });
    }
}
