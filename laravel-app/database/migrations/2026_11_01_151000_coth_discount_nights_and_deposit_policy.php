<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CothDiscountNightsAndDepositPolicy extends Migration
{
    public function up()
    {
        if (Schema::hasTable('coth_settings') && ! Schema::hasColumn('coth_settings', 'deposit_refund_policy')) {
            Schema::table('coth_settings', function (Blueprint $table) {
                $table->string('deposit_refund_policy', 32)->nullable()->after('deposit_percent');
            });
        }

        if (Schema::hasTable('coth_settings')) {
            $dates = json_encode(app(\App\Services\Coth\CothCatalog::class)->discountDates());
            DB::table('coth_settings')->update([
                'discount_percent' => 20,
                'discount_dates' => $dates,
            ]);
        }

        if (Schema::hasTable('whatsapp_settings')) {
            $now = now();
            $key = 'AI_ESCALATION_WHATSAPP_NUMBER';
            $value = '237673125011';
            $row = DB::table('whatsapp_settings')->where('key', $key)->first();
            if ($row) {
                DB::table('whatsapp_settings')->where('key', $key)->update([
                    'value' => $value,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('whatsapp_settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('coth_settings') && Schema::hasColumn('coth_settings', 'deposit_refund_policy')) {
            Schema::table('coth_settings', function (Blueprint $table) {
                $table->dropColumn('deposit_refund_policy');
            });
        }
    }
}
