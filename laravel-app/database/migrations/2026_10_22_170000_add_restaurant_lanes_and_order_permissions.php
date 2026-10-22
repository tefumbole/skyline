<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddRestaurantLanesAndOrderPermissions extends Migration
{
    public function up()
    {
        if (Schema::hasTable('categories') && ! Schema::hasColumn('categories', 'service')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('service', 30)->nullable();
            });
        }

        if (Schema::hasTable('restaurant_tables')) {
            $tableColumns = [];
            if (! Schema::hasColumn('restaurant_tables', 'description')) {
                $tableColumns[] = 'description';
            }
            if (! Schema::hasColumn('restaurant_tables', 'capacity')) {
                $tableColumns[] = 'capacity';
            }
            if ($tableColumns) {
                Schema::table('restaurant_tables', function (Blueprint $table) use ($tableColumns) {
                    if (in_array('description', $tableColumns, true)) {
                        $table->string('description')->nullable();
                    }
                    if (in_array('capacity', $tableColumns, true)) {
                        $table->unsignedInteger('capacity')->nullable();
                    }
                });
            }
        }

        if (Schema::hasTable('restaurant_order_items')) {
            $itemColumns = [];
            if (! Schema::hasColumn('restaurant_order_items', 'lane')) {
                $itemColumns[] = 'lane';
            }
            if (! Schema::hasColumn('restaurant_order_items', 'previous_status')) {
                $itemColumns[] = 'previous_status';
            }
            if ($itemColumns) {
                Schema::table('restaurant_order_items', function (Blueprint $table) use ($itemColumns) {
                    if (in_array('lane', $itemColumns, true)) {
                        $table->string('lane', 20)->nullable();
                    }
                    if (in_array('previous_status', $itemColumns, true)) {
                        $table->string('previous_status', 20)->nullable();
                    }
                });
            }
            if (Schema::hasColumn('restaurant_order_items', 'lane')) {
                $rows = DB::table('restaurant_order_items as i')
                    ->join('restaurant_orders as o', 'o.id', '=', 'i.restaurant_order_id')
                    ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
                    ->whereNull('i.lane')
                    ->get(['i.id', 'i.department', 'o.source', 'p.category_id']);
                foreach ($rows as $row) {
                    $lane = \App\Services\Restaurant\RestaurantDepartment::laneForCategoryId($row->category_id);
                    if (! $lane) {
                        $lane = $row->department === 'food' ? 'restaurant' : ($row->department === 'drinks' ? 'bar' : null);
                    }
                    if ($lane && $row->source === 'frontend') {
                        $lane = 'online';
                    }
                    if ($lane) {
                        DB::table('restaurant_order_items')->where('id', $row->id)->update(['lane' => $lane]);
                    }
                }
            }
        }

        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $names = [];
        foreach (\App\Services\Restaurant\RestaurantAccess::permissionRows() as $row) {
            foreach (array_slice($row, 1) as $name) {
                $names[] = $name;
            }
        }
        foreach ($names as $name) {
            if (! DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->exists()) {
                DB::table('permissions')->insert([
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            Permission::findOrCreate($name, 'web');
        }
        foreach ([1, 2] as $roleId) {
            $role = Role::where('id', $roleId)->where('guard_name', 'web')->first();
            if (! $role) {
                continue;
            }
            foreach ($names as $name) {
                if (! $role->hasPermissionTo($name)) {
                    $role->givePermissionTo($name);
                }
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('restaurant_order_items') && Schema::hasColumn('restaurant_order_items', 'lane')) {
            Schema::table('restaurant_order_items', function (Blueprint $table) {
                $table->dropColumn('lane');
            });
        }
    }
}
