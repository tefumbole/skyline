<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPersonalizedToLetters extends Migration
{
    public function up()
    {
        if (Schema::hasTable('letters') && ! Schema::hasColumn('letters', 'personalized')) {
            Schema::table('letters', function (Blueprint $table) {
                $table->boolean('personalized')->default(false);
            });
        }
    }

    public function down()
    {
    }
}
