<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlaceFoodMenuUnderFoodCategory extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('categories') || ! Schema::hasTable('products')) {
            return;
        }

        $now = now();
        $restaurant = DB::table('categories')->where('name', 'Restaurant')->whereNull('parent_id')->where('is_active', 1)->first();
        $food = DB::table('categories')->where('name', 'Food')->where('is_active', 1)->orderBy('id')->first();

        if (! $food) {
            $row = [
                'name' => 'Food',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (Schema::hasColumn('categories', 'parent_id')) {
                $row['parent_id'] = $restaurant ? $restaurant->id : null;
            }
            if (Schema::hasColumn('categories', 'department')) {
                $row['department'] = 'food';
            }
            if (Schema::hasColumn('categories', 'service')) {
                $row['service'] = 'restaurant';
            }
            if (Schema::hasColumn('categories', 'image')) {
                $row['image'] = null;
            }
            if (Schema::hasColumn('categories', 'cloud_tenant_id') && Schema::hasTable('cloud_tenants')) {
                $row['cloud_tenant_id'] = DB::table('cloud_tenants')->orderBy('id')->value('id');
            }
            $foodId = DB::table('categories')->insertGetId($row);
        } else {
            $foodId = $food->id;
            $update = ['updated_at' => $now];
            if ($restaurant && Schema::hasColumn('categories', 'parent_id') && (int) $food->parent_id !== (int) $restaurant->id) {
                $update['parent_id'] = $restaurant->id;
            }
            if (Schema::hasColumn('categories', 'department')) {
                $update['department'] = 'food';
            }
            if (Schema::hasColumn('categories', 'service')) {
                $update['service'] = 'restaurant';
            }
            DB::table('categories')->where('id', $foodId)->update($update);
        }

        if ($restaurant) {
            DB::table('products')->where('category_id', $restaurant->id)->update(['category_id' => $foodId]);
        }
    }

    public function down()
    {
    }
}
