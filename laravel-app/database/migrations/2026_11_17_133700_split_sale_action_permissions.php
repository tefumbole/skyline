<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SplitSaleActionPermissions extends Migration
{
    public function up()
    {
        $addId = DB::table('permissions')->where('name', 'sales-add')->where('guard_name', 'web')->value('id');
        if (! $addId) {
            return;
        }
        $roleIds = DB::table('role_has_permissions')->where('permission_id', $addId)->pluck('role_id');
        foreach (['sales-pos', 'sales-import'] as $name) {
            $id = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->value('id');
            if (! $id) {
                $id = DB::table('permissions')->insertGetId([
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
            foreach ($roleIds as $roleId) {
                $exists = DB::table('role_has_permissions')->where([
                    'permission_id' => $id,
                    'role_id' => $roleId,
                ])->exists();
                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $id,
                        'role_id' => $roleId,
                    ]);
                }
            }
        }
    }

    public function down()
    {
    }
}
