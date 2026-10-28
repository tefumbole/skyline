<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CothGrayTealArrangement extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('coth_tables')) {
            return;
        }

        $this->renameHallEights();
        $this->applyStyles();
        $this->addYardSixes();
        $this->removeExtraOuterFour();
    }

    public function down()
    {
    }

    protected function renameHallEights()
    {
        $map = [
            'HALL-T6-06' => ['HALL-T8-01', 'couch-gray'],
            'HALL-T6-07' => ['HALL-T8-02', 'couch-teal'],
            'HALL-T6-08' => ['HALL-T8-03', 'couch-gray'],
        ];
        foreach ($map as $from => $to) {
            if (DB::table('coth_tables')->where('code', $to[0])->exists()) {
                continue;
            }
            DB::table('coth_tables')->where('code', $from)->update($this->fields([
                'code' => $to[0],
                'name' => $to[0],
                'capacity' => 8,
                'price' => 600000,
                'seating_style' => 'couch',
                'visual_preset' => $to[1],
                'is_high_table' => 0,
                'is_city_view' => 0,
            ]));
        }
    }

    protected function applyStyles()
    {
        $rows = [
            'YARD-T4-01' => ['high_table', 'high-gray', 1, 1],
            'YARD-T4-02' => ['high_table', 'high-teal', 1, 1],
            'YARD-T4-03' => ['high_table', 'high-gray', 1, 1],
            'YARD-T4-04' => ['high_table', 'high-teal', 1, 1],
            'YARD-T6-01' => ['couch', 'couch-gray', 0, 0],
            'YARD-T6-02' => ['high_table', 'high-teal', 1, 1],
            'YARD-T6-03' => ['high_table', 'high-gray', 1, 1],
            'YARD-T6-04' => ['high_table', 'high-teal', 1, 1],
            'YARD-T8-01' => ['couch', 'couch-gray', 0, 0],
            'YARD-T8-02' => ['couch', 'couch-teal', 0, 0],
            'YARD-T10-01' => ['couch', 'couch-gray', 0, 0],
            'YARD-T10-02' => ['couch', 'couch-teal', 0, 0],
            'HALL-T4-01' => ['couch', 'couch-gray', 0, 0],
            'HALL-T4-02' => ['couch', 'couch-teal', 0, 0],
            'HALL-T6-01' => ['couch', 'couch-teal', 0, 0],
            'HALL-T6-02' => ['couch', 'couch-gray', 0, 0],
            'HALL-T6-03' => ['couch', 'couch-teal', 0, 0],
            'HALL-T6-04' => ['couch', 'couch-gray', 0, 0],
            'HALL-T6-05' => ['couch', 'couch-teal', 0, 0],
            'HALL-T8-01' => ['couch', 'couch-gray', 0, 0],
            'HALL-T8-02' => ['couch', 'couch-teal', 0, 0],
            'HALL-T8-03' => ['couch', 'couch-gray', 0, 0],
            'HALL-T10-01' => ['couch', 'couch-gray', 0, 0],
            'HALL-T10-02' => ['couch', 'couch-teal', 0, 0],
            'OUTER-T4-01' => ['standard', 'chair-gray', 0, 0],
            'OUTER-T4-02' => ['standard', 'chair-teal', 0, 0],
            'OUTER-T4-03' => ['standard', 'chair-gray', 0, 0],
            'OUTER-T4-04' => ['standard', 'chair-teal', 0, 0],
            'OUTER-T6-01' => ['couch', 'couch-teal', 0, 0],
            'OUTER-T8-01' => ['couch', 'couch-gray', 0, 0],
            'OUTER-T8-02' => ['couch', 'couch-teal', 0, 0],
            'OUTER-T10-01' => ['couch', 'couch-gray', 0, 0],
            'POOL-T2-01' => ['high_table', 'high-gray', 1, 0],
            'POOL-T2-02' => ['high_table', 'high-teal', 1, 0],
            'POOL-T3-01' => ['high_table', 'high-gray', 1, 0],
            'POOL-T4-01' => ['high_table', 'high-teal', 1, 0],
            'POOL-T4-02' => ['high_table', 'high-gray', 1, 0],
            'POOL-T4-03' => ['high_table', 'high-teal', 1, 0],
            'POOL-T4-04' => ['high_table', 'high-gray', 1, 0],
            'POOL-T4-05' => ['high_table', 'high-teal', 1, 0],
            'POOL-T6-01' => ['couch', 'couch-gray', 0, 0],
        ];
        foreach ($rows as $code => $spec) {
            DB::table('coth_tables')->where('code', $code)->update($this->fields([
                'seating_style' => $spec[0],
                'visual_preset' => $spec[1],
                'is_high_table' => $spec[2],
                'is_city_view' => $spec[3],
            ]));
        }
        DB::table('coth_tables')->where('code', 'like', 'HALL-T8-%')->update(['price' => 600000]);
    }

    protected function addYardSixes()
    {
        $sample = DB::table('coth_tables')->where('code', 'YARD-T6-01')->first();
        if (! $sample) {
            return;
        }
        $dates = DB::table('coth_slots')->where('coth_table_id', $sample->id)->where('is_enabled', 1)->pluck('event_date');
        $now = date('Y-m-d H:i:s');
        $extra = [
            'YARD-T6-05' => 'high-gray',
            'YARD-T6-06' => 'high-teal',
        ];
        foreach ($extra as $code => $preset) {
            if (DB::table('coth_tables')->where('code', $code)->exists()) {
                continue;
            }
            $id = DB::table('coth_tables')->insertGetId($this->fields([
                'coth_section_id' => $sample->coth_section_id,
                'code' => $code,
                'name' => $code,
                'capacity' => 6,
                'seating_style' => 'high_table',
                'visual_preset' => $preset,
                'is_high_table' => 1,
                'is_city_view' => 1,
                'price' => $sample->price,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ], false));
            $slots = [];
            foreach ($dates as $date) {
                $slots[] = [
                    'coth_table_id' => $id,
                    'event_date' => $date,
                    'status' => 'open',
                    'is_enabled' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($slots) {
                DB::table('coth_slots')->insert($slots);
            }
        }
    }

    protected function removeExtraOuterFour()
    {
        $id = DB::table('coth_tables')->where('code', 'OUTER-T4-05')->value('id');
        if (! $id) {
            return;
        }
        if (Schema::hasTable('coth_reservations') && DB::table('coth_reservations')->where('coth_table_id', $id)->exists()) {
            DB::table('coth_tables')->where('id', $id)->update(['is_active' => 0]);
            DB::table('coth_slots')->where('coth_table_id', $id)->update(['is_enabled' => 0]);

            return;
        }
        DB::table('coth_slots')->where('coth_table_id', $id)->delete();
        DB::table('coth_tables')->where('id', $id)->delete();
    }

    protected function fields(array $fields, $touch = true)
    {
        foreach (['tabletop_material', 'tabletop_color', 'upholstery_color', 'cushion_colors', 'trim_color'] as $col) {
            if (Schema::hasColumn('coth_tables', $col)) {
                $fields[$col] = null;
            }
        }
        if ($touch) {
            $fields['updated_at'] = date('Y-m-d H:i:s');
        }

        return $fields;
    }
}
