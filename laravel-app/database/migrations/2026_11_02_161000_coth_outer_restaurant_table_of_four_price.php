<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CothOuterRestaurantTableOfFourPrice extends Migration
{
    public function up()
    {
        $this->setPrice(400000);
    }

    public function down()
    {
        $this->setPrice(500000);
    }

    protected function setPrice($price)
    {
        if (! Schema::hasTable('coth_tables') || ! Schema::hasTable('coth_sections')) {
            return;
        }

        $sectionIds = DB::table('coth_sections')->where('code', 'outer')->pluck('id');
        if ($sectionIds->isEmpty()) {
            return;
        }

        DB::table('coth_tables')
            ->whereIn('coth_section_id', $sectionIds->all())
            ->where('capacity', 4)
            ->update(['price' => $price]);
    }
}
