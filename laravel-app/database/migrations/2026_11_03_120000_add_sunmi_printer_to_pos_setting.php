<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSunmiPrinterToPosSetting extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('pos_setting')) {
            return;
        }
        Schema::table('pos_setting', function (Blueprint $table) {
            if (! Schema::hasColumn('pos_setting', 'sunmi_print_enabled')) {
                $table->boolean('sunmi_print_enabled')->default(0);
            }
            if (! Schema::hasColumn('pos_setting', 'sunmi_paper_mm')) {
                $table->unsignedSmallInteger('sunmi_paper_mm')->default(80);
            }
        });
    }

    public function down()
    {
    }
}
