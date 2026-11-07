<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCothMultiDayStays extends Migration
{
    public function up()
    {
        if (Schema::hasTable('coth_reservations')) {
            Schema::table('coth_reservations', function (Blueprint $table) {
                if (! Schema::hasColumn('coth_reservations', 'nights')) {
                    $table->unsignedSmallInteger('nights')->default(1);
                }
                if (! Schema::hasColumn('coth_reservations', 'stay_discount_percent')) {
                    $table->unsignedSmallInteger('stay_discount_percent')->default(0);
                }
            });
        }

        if (! Schema::hasTable('coth_reservation_days')) {
            Schema::create('coth_reservation_days', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('coth_reservation_id');
                $table->unsignedInteger('coth_slot_id');
                $table->date('event_date');
                $table->timestamps();
                $table->unique(['coth_reservation_id', 'event_date']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('coth_reservation_days');
        if (Schema::hasTable('coth_reservations') && Schema::hasColumn('coth_reservations', 'stay_discount_percent')) {
            Schema::table('coth_reservations', function (Blueprint $table) {
                $table->dropColumn('stay_discount_percent');
            });
        }
        if (Schema::hasTable('coth_reservations') && Schema::hasColumn('coth_reservations', 'nights')) {
            Schema::table('coth_reservations', function (Blueprint $table) {
                $table->dropColumn('nights');
            });
        }
    }
}
