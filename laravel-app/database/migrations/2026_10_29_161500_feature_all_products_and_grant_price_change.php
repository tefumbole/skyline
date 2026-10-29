<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FeatureAllProductsAndGrantPriceChange extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('products', 'featured')) {
            DB::table('products')->where('is_active', 1)->update(['featured' => 1]);
        }

        $now = date('Y-m-d H:i:s');
        foreach (['price-change', 'search_all_products'] as $name) {
            if (DB::table('permissions')->where('name', $name)->exists()) {
                continue;
            }
            $row = [
                'name' => $name,
                'guard_name' => 'web',
            ];
            if (Schema::hasColumn('permissions', 'created_at')) {
                $row['created_at'] = $now;
                $row['updated_at'] = $now;
            }
            DB::table('permissions')->insert($row);
        }

        $roleId = DB::table('roles')->where('name', 'Super Admin')->value('id');
        if ($roleId) {
            $have = array_flip(DB::table('role_has_permissions')->where('role_id', $roleId)->pluck('permission_id')->all());
            foreach (DB::table('permissions')->pluck('id') as $permissionId) {
                if (isset($have[$permissionId])) {
                    continue;
                }
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }

        if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down()
    {
    }
}
