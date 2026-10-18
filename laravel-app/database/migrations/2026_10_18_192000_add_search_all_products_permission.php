<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSearchAllProductsPermission extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }
        $exists = DB::table('permissions')->where('name', 'search_all_products')->where('guard_name', 'web')->exists();
        if (! $exists) {
            $now = date('Y-m-d H:i:s');
            DB::table('permissions')->insert([
                'name' => 'search_all_products',
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
    }
}
