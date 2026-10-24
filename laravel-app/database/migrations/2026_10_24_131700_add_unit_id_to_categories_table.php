<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnitIdToCategoriesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('categories') && ! Schema::hasColumn('categories', 'unit_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->integer('unit_id')->nullable()->after('parent_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'unit_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('unit_id');
            });
        }
    }
}
