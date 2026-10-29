<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetCothSharedFurnitureLooks extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('coth_tables') || ! Schema::hasTable('coth_sections')) {
            return;
        }

        $sectionIds = DB::table('coth_sections')->whereIn('code', ['yard', 'outer'])->pluck('id');
        if (count($sectionIds)) {
            DB::table('coth_tables')
                ->whereIn('coth_section_id', $sectionIds)
                ->where('seating_style', 'couch')
                ->update([
                    'visual_preset' => 'couch-sand',
                    'is_high_table' => 0,
                    'tabletop_material' => 'Walnut',
                    'tabletop_color' => '#5C3A1E',
                    'upholstery_color' => '#C4B39A',
                    'cushion_colors' => 'Sand and dark brown',
                    'trim_color' => '#C6A15B',
                ]);
        }

        DB::table('coth_tables')
            ->where('seating_style', 'high_table')
            ->update([
                'visual_preset' => 'high-stool',
                'is_high_table' => 1,
                'tabletop_material' => 'Walnut',
                'tabletop_color' => '#6B3E26',
                'upholstery_color' => '#8B6B52',
                'cushion_colors' => 'Brown with a dark lumbar pillow',
                'trim_color' => '#C6A15B',
            ]);
    }

    public function down()
    {
    }
}
