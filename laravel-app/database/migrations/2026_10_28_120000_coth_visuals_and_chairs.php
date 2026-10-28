<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CothVisualsAndChairs extends Migration
{
    public function up()
    {
        if (Schema::hasTable('coth_tables')) {
            Schema::table('coth_tables', function (Blueprint $table) {
                if (! Schema::hasColumn('coth_tables', 'visual_preset')) {
                    $table->string('visual_preset', 64)->nullable()->after('seating_style');
                }
                if (! Schema::hasColumn('coth_tables', 'is_city_view')) {
                    $table->boolean('is_city_view')->default(0)->after('visual_preset');
                }
                if (! Schema::hasColumn('coth_tables', 'is_high_table')) {
                    $table->boolean('is_high_table')->default(0)->after('is_city_view');
                }
                if (! Schema::hasColumn('coth_tables', 'allow_extra_chairs')) {
                    $table->boolean('allow_extra_chairs')->default(0)->after('is_high_table');
                }
                if (! Schema::hasColumn('coth_tables', 'max_extra_chairs')) {
                    $table->unsignedSmallInteger('max_extra_chairs')->default(0)->after('allow_extra_chairs');
                }
                if (! Schema::hasColumn('coth_tables', 'extra_chair_rate')) {
                    $table->integer('extra_chair_rate')->nullable()->after('max_extra_chairs');
                }
                if (! Schema::hasColumn('coth_tables', 'extra_chair_furniture_type')) {
                    $table->string('extra_chair_furniture_type', 64)->nullable()->after('extra_chair_rate');
                }
            });
        }

        if (Schema::hasTable('coth_reservations')) {
            Schema::table('coth_reservations', function (Blueprint $table) {
                if (! Schema::hasColumn('coth_reservations', 'extra_chairs_qty')) {
                    $table->unsignedSmallInteger('extra_chairs_qty')->default(0)->after('deposit_due');
                }
                if (! Schema::hasColumn('coth_reservations', 'extra_chair_rate')) {
                    $table->integer('extra_chair_rate')->nullable()->after('extra_chairs_qty');
                }
                if (! Schema::hasColumn('coth_reservations', 'extra_chairs_amount')) {
                    $table->unsignedInteger('extra_chairs_amount')->default(0)->after('extra_chair_rate');
                }
                if (! Schema::hasColumn('coth_reservations', 'booking_total')) {
                    $table->unsignedInteger('booking_total')->nullable()->after('extra_chairs_amount');
                }
            });

            // Backfill booking_total for existing rows so queries keep working.
            try {
                \Illuminate\Support\Facades\DB::statement('UPDATE coth_reservations SET booking_total = final_price WHERE booking_total IS NULL');
            } catch (\Throwable $e) {
                \Log::warning('coth backfill booking_total: '.$e->getMessage());
            }
        }

        if (Schema::hasTable('coth_settings')) {
            Schema::table('coth_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('coth_settings', 'discount_includes_addons')) {
                    $table->boolean('discount_includes_addons')->default(0)->after('discount_dates');
                }
            });
        }

        if (! Schema::hasTable('coth_extra_chair_inventory')) {
            Schema::create('coth_extra_chair_inventory', function (Blueprint $table) {
                $table->increments('id');
                $table->date('event_date')->unique();
                $table->unsignedInteger('total_capacity')->nullable(); // null = no shared cap, only per-table maxes apply
                $table->unsignedInteger('held')->default(0);
                $table->unsignedInteger('booked')->default(0);
                $table->timestamps();
            });
        }

        // Backfill: give each existing table a persistent visual preset based
        // on its code so OUTER-T10-01 and OUTER-T10-02 look different. Admin
        // can change it any time.
        if (Schema::hasTable('coth_tables')) {
            try {
                $standardPresets = ['walnut-navy', 'oak-teal', 'marble-cream'];
                $couchPresets = ['couch-navy', 'couch-teal'];
                $rows = \Illuminate\Support\Facades\DB::table('coth_tables')->whereNull('visual_preset')->get();
                foreach ($rows as $row) {
                    $hash = crc32((string) $row->code);
                    if ($row->seating_style === 'couch') {
                        $preset = $couchPresets[$hash % count($couchPresets)];
                    } else {
                        $preset = $standardPresets[$hash % count($standardPresets)];
                    }
                    \Illuminate\Support\Facades\DB::table('coth_tables')->where('id', $row->id)->update(['visual_preset' => $preset]);
                }
            } catch (\Throwable $e) {
                \Log::warning('coth visual preset backfill: '.$e->getMessage());
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('coth_extra_chair_inventory');

        if (Schema::hasTable('coth_settings')) {
            Schema::table('coth_settings', function (Blueprint $table) {
                if (Schema::hasColumn('coth_settings', 'discount_includes_addons')) {
                    $table->dropColumn('discount_includes_addons');
                }
            });
        }

        if (Schema::hasTable('coth_reservations')) {
            Schema::table('coth_reservations', function (Blueprint $table) {
                foreach (['extra_chairs_qty', 'extra_chair_rate', 'extra_chairs_amount', 'booking_total'] as $col) {
                    if (Schema::hasColumn('coth_reservations', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('coth_tables')) {
            Schema::table('coth_tables', function (Blueprint $table) {
                foreach ([
                    'visual_preset', 'is_city_view', 'is_high_table',
                    'allow_extra_chairs', 'max_extra_chairs', 'extra_chair_rate', 'extra_chair_furniture_type',
                ] as $col) {
                    if (Schema::hasColumn('coth_tables', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
}
