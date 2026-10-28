<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CothCoffeeVisuals extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('coth_tables')) {
            return;
        }

        Schema::table('coth_tables', function (Blueprint $table) {
            if (! Schema::hasColumn('coth_tables', 'tabletop_material')) {
                $table->string('tabletop_material', 64)->nullable()->after('visual_preset');
            }
            if (! Schema::hasColumn('coth_tables', 'tabletop_color')) {
                $table->string('tabletop_color', 32)->nullable()->after('tabletop_material');
            }
            if (! Schema::hasColumn('coth_tables', 'upholstery_color')) {
                $table->string('upholstery_color', 32)->nullable()->after('tabletop_color');
            }
            if (! Schema::hasColumn('coth_tables', 'cushion_colors')) {
                $table->string('cushion_colors', 191)->nullable()->after('upholstery_color');
            }
            if (! Schema::hasColumn('coth_tables', 'trim_color')) {
                $table->string('trim_color', 32)->nullable()->after('cushion_colors');
            }
        });

        $looks = [
            'cream-walnut' => ['Walnut', '#5C3A1E', '#F3E6D0', 'Caramel and chocolate', '#C6A15B'],
            'taupe-marble' => ['Dark marble', '#2E2A28', '#C4B5A4', 'Ivory and coffee brown', '#C6A15B'],
            'camel-oak' => ['Oak', '#A97845', '#C4A574', 'Cream and espresso', '#C6A15B'],
            'ivory-walnut' => ['Dark walnut', '#3B2416', '#F6EFE4', 'Caramel and taupe', '#C6A15B'],
            'high-walnut' => ['Walnut', '#5C3A1E', '#F3E6D0', 'Cream', '#C6A15B'],
            'high-oak' => ['Oak', '#A97845', '#C4A574', 'Camel', '#C6A15B'],
        ];

        $couches = [
            'OUTER-T10-01' => 'cream-walnut',
            'OUTER-T10-02' => 'taupe-marble',
            'OUTER-T8-01' => 'cream-walnut',
            'OUTER-T8-02' => 'taupe-marble',
            'OUTER-T6-01' => 'cream-walnut',
            'YARD-T10-01' => 'taupe-marble',
            'YARD-T10-02' => 'camel-oak',
            'YARD-T8-01' => 'camel-oak',
            'YARD-T8-02' => 'ivory-walnut',
            'YARD-T6-01' => 'ivory-walnut',
            'YARD-T6-02' => 'cream-walnut',
            'YARD-T6-03' => 'taupe-marble',
            'YARD-T6-04' => 'camel-oak',
        ];
        $high = [
            'YARD-T4-01' => 'high-walnut',
            'YARD-T4-02' => 'high-oak',
            'YARD-T4-03' => 'high-walnut',
            'YARD-T4-04' => 'high-oak',
        ];

        foreach ($couches as $code => $preset) {
            $this->paint($code, $preset, 'couch', 0, 0, 'complementary side chair', $looks[$preset]);
        }
        foreach ($high as $code => $preset) {
            $this->paint($code, $preset, 'high_table', 1, 1, 'matching high chair', $looks[$preset]);
        }
    }

    public function down()
    {
        if (! Schema::hasTable('coth_tables')) {
            return;
        }
        Schema::table('coth_tables', function (Blueprint $table) {
            foreach (['trim_color', 'cushion_colors', 'upholstery_color', 'tabletop_color', 'tabletop_material'] as $col) {
                if (Schema::hasColumn('coth_tables', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    protected function paint($code, $preset, $style, $cityView, $highTable, $chairNote, array $look)
    {
        DB::table('coth_tables')->where('code', $code)->update([
            'seating_style' => $style,
            'visual_preset' => $preset,
            'is_city_view' => $cityView,
            'is_high_table' => $highTable,
            'tabletop_material' => $look[0],
            'tabletop_color' => $look[1],
            'upholstery_color' => $look[2],
            'cushion_colors' => $look[3],
            'trim_color' => $look[4],
            'extra_chair_furniture_type' => $chairNote,
        ]);
    }
}
