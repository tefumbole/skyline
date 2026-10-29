<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateManualTestResults extends Migration
{
    public function up()
    {
        if (Schema::hasTable('manual_test_results')) {
            return;
        }

        Schema::create('manual_test_results', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('item_key', 80);
            $table->string('result', 8);
            $table->timestamps();
            $table->unique(['user_id', 'item_key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('manual_test_results');
    }
}
