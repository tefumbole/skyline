<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPhoneToManualTestReports extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('manual_test_reports')) {
            return;
        }
        Schema::table('manual_test_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('manual_test_reports', 'phone')) {
                $table->string('phone', 32)->nullable()->index();
            }
            if (! Schema::hasColumn('manual_test_reports', 'status')) {
                $table->string('status', 20)->default('submitted');
            }
            if (! Schema::hasColumn('manual_test_reports', 'stage')) {
                $table->unsignedTinyInteger('stage')->default(1);
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('manual_test_reports')) {
            return;
        }
        Schema::table('manual_test_reports', function (Blueprint $table) {
            if (Schema::hasColumn('manual_test_reports', 'stage')) {
                $table->dropColumn('stage');
            }
            if (Schema::hasColumn('manual_test_reports', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('manual_test_reports', 'phone')) {
                $table->dropColumn('phone');
            }
        });
    }
}
