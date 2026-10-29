<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuthorizeWhatsappOwnerPhone extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('whatsapp_owner_users') || ! Schema::hasTable('users')) {
            return;
        }

        $phone = '237675321739';
        $userId = DB::table('users')->where('phone', $phone)->value('id');
        if (! $userId) {
            $userId = DB::table('users')->where('phone', '+'.$phone)->value('id');
        }
        if (! $userId) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $existing = DB::table('whatsapp_owner_users')
            ->where('user_id', $userId)
            ->where('normalized_phone', $phone)
            ->first();
        if ($existing) {
            DB::table('whatsapp_owner_users')->where('id', $existing->id)->update([
                'enabled' => 1,
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('whatsapp_owner_users')->insert([
            'user_id' => $userId,
            'normalized_phone' => $phone,
            'enabled' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down()
    {
        if (! Schema::hasTable('whatsapp_owner_users')) {
            return;
        }

        DB::table('whatsapp_owner_users')->where('normalized_phone', '237675321739')->delete();
    }
}
