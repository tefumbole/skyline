<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedRestaurantTables1To25 extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('restaurant_tables')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $names = DB::table('restaurant_tables')->pluck('name')->map(function ($name) {
            return strtolower(trim((string) $name));
        })->all();

        for ($number = 1; $number <= 25; $number++) {
            $label = 'Table '.$number;
            if (in_array(strtolower($label), $names, true) || in_array((string) $number, $names, true)) {
                continue;
            }
            DB::table('restaurant_tables')->insert([
                'name' => $label,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $names[] = strtolower($label);
        }
    }

    public function down()
    {
    }
}
