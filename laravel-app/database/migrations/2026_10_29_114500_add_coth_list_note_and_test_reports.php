<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCothListNoteAndTestReports extends Migration
{
    public function up()
    {
        if (Schema::hasTable('coth_tables') && ! Schema::hasColumn('coth_tables', 'list_note')) {
            Schema::table('coth_tables', function (Blueprint $table) {
                $table->string('list_note', 255)->nullable();
            });
        }

        if (! Schema::hasTable('manual_test_reports')) {
            Schema::create('manual_test_reports', function (Blueprint $table) {
                $table->increments('id');
                $table->string('tester_name', 120);
                $table->longText('payload');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('coth_tables') && Schema::hasColumn('coth_tables', 'list_note')) {
            Schema::table('coth_tables', function (Blueprint $table) {
                $table->dropColumn('list_note');
            });
        }
        Schema::dropIfExists('manual_test_reports');
    }
}
