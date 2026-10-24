<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSaleLineColumnsToProductSalesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('product_sales')) {
            return;
        }

        Schema::table('product_sales', function (Blueprint $table) {
            if (! Schema::hasColumn('product_sales', 'category_id')) {
                $table->integer('category_id')->nullable()->after('product_id');
            }
            if (! Schema::hasColumn('product_sales', 'multi_product_batch_id')) {
                $table->text('multi_product_batch_id')->nullable()->after('product_batch_id');
            }
            if (! Schema::hasColumn('product_sales', 'multi_product_batch_qty')) {
                $table->text('multi_product_batch_qty')->nullable()->after('multi_product_batch_id');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('product_sales')) {
            return;
        }

        Schema::table('product_sales', function (Blueprint $table) {
            foreach (['multi_product_batch_qty', 'multi_product_batch_id', 'category_id'] as $column) {
                if (Schema::hasColumn('product_sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
