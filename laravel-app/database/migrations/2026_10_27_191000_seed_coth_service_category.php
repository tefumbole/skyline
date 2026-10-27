<?php

use Illuminate\Database\Migrations\Migration;

class SeedCothServiceCategory extends Migration
{
    public function up()
    {
        app(\App\Services\Coth\CothCatalog::class)->seed();
    }

    public function down()
    {
    }
}
