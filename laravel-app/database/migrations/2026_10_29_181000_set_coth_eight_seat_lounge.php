<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetCothEightSeatLounge extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('coth_tables')) {
            return;
        }

        DB::table('coth_tables')
            ->where('seating_style', 'couch')
            ->where('capacity', 8)
            ->update([
                'visual_preset' => 'couch-eight',
                'is_high_table' => 0,
                'tabletop_material' => 'Teak',
                'tabletop_color' => '#C4783A',
                'upholstery_color' => '#6B6E73',
                'cushion_colors' => 'Gray with beige and rust pillows',
                'trim_color' => '#C4783A',
            ]);
    }

    public function down()
    {
    }
}
