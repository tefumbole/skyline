<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPosColumnsAndPermissions extends Migration
{
    public function up()
    {
        $this->addColumn('accounts', 'is_default_debit', function (Blueprint $table) {
            $table->boolean('is_default_debit')->default(0);
        });
        $this->addColumn('accounts', 'department_id', function (Blueprint $table) {
            $table->unsignedInteger('department_id')->nullable();
        });
        $this->addColumn('products', 'location', function (Blueprint $table) {
            $table->string('location')->nullable();
        });
        $this->addColumn('products', 'vendor_id', function (Blueprint $table) {
            $table->unsignedInteger('vendor_id')->nullable();
        });
        $this->addColumn('product_batches', 'location', function (Blueprint $table) {
            $table->string('location')->nullable();
        });
        $this->addColumn('asset_expenses', 'type', function (Blueprint $table) {
            $table->string('type')->nullable();
        });
        $this->addColumn('asset_expenses', 'activity_type', function (Blueprint $table) {
            $table->string('activity_type')->nullable();
        });

        $now = date('Y-m-d H:i:s');
        $this->seedEmptySettings($now);
        $this->restoreStructuralRows($now);

        if (Schema::hasTable('permissions')) {
            foreach (['suppliers-add', 'today_sale', 'today_profit', 'holiday'] as $name) {
                $exists = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->exists();
                if (! $exists) {
                    DB::table('permissions')->insert([
                        'name' => $name,
                        'guard_name' => 'web',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down()
    {
        $this->dropColumn('accounts', 'is_default_debit');
        $this->dropColumn('accounts', 'department_id');
        $this->dropColumn('products', 'location');
        $this->dropColumn('products', 'vendor_id');
        $this->dropColumn('product_batches', 'location');
        $this->dropColumn('asset_expenses', 'type');
        $this->dropColumn('asset_expenses', 'activity_type');
    }

    protected function seedEmptySettings($now)
    {
        if (Schema::hasTable('pos_setting') && DB::table('pos_setting')->count() === 0) {
            $row = [
                'id' => 1,
                'customer_id' => (int) (Schema::hasTable('customers') ? DB::table('customers')->value('id') : 0),
                'warehouse_id' => (int) (Schema::hasTable('warehouses') ? DB::table('warehouses')->value('id') : 0),
                'biller_id' => (int) (Schema::hasTable('billers') ? DB::table('billers')->value('id') : 0),
                'product_number' => 10,
                'stripe_public_key' => '',
                'stripe_secret_key' => '',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (Schema::hasColumn('pos_setting', 'keybord_active')) {
                $row['keybord_active'] = 0;
            }
            DB::table('pos_setting')->insert($row);
        }

        if (Schema::hasTable('reward_point_settings') && DB::table('reward_point_settings')->count() === 0) {
            DB::table('reward_point_settings')->insert([
                'per_point_amount' => 0,
                'minimum_amount' => 0,
                'duration' => 0,
                'type' => 'amount',
                'is_active' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (Schema::hasTable('hrm_settings') && DB::table('hrm_settings')->count() === 0) {
            DB::table('hrm_settings')->insert([
                'checkin' => '08:00',
                'checkout' => '17:00',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    protected function restoreStructuralRows($now)
    {
        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'department')) {
            foreach (['Food' => 'food', 'Drinks' => 'drinks'] as $name => $department) {
                $existing = DB::table('categories')->where('name', $name)->first();
                if (! $existing) {
                    $row = [
                        'name' => $name,
                        'department' => $department,
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if (Schema::hasColumn('categories', 'parent_id')) {
                        $row['parent_id'] = null;
                    }
                    if (Schema::hasColumn('categories', 'image')) {
                        $row['image'] = null;
                    }
                    if (Schema::hasColumn('categories', 'cloud_tenant_id')) {
                        $row['cloud_tenant_id'] = DB::table('cloud_tenants')->orderBy('id')->value('id');
                    }
                    DB::table('categories')->insert($row);
                }
            }
        }

        if (! Schema::hasTable('cloud_internal_entitlements') || ! Schema::hasTable('cloud_modules') || ! Schema::hasTable('cloud_tenants')) {
            return;
        }
        $tenantId = DB::table('cloud_tenants')->orderBy('id')->value('id');
        if (! $tenantId) {
            return;
        }
        foreach (['WHATSAPP_HUB', 'SALES_INVOICES', 'RENTALS'] as $code) {
            $moduleId = DB::table('cloud_modules')->where('code', $code)->value('id');
            if (! $moduleId) {
                continue;
            }
            $exists = DB::table('cloud_internal_entitlements')
                ->where('cloud_tenant_id', $tenantId)
                ->where('cloud_module_id', $moduleId)
                ->exists();
            if (! $exists) {
                DB::table('cloud_internal_entitlements')->insert([
                    'cloud_tenant_id' => $tenantId,
                    'cloud_module_id' => $moduleId,
                    'enabled' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    protected function addColumn($tableName, $column, $callback)
    {
        if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, $column)) {
            Schema::table($tableName, $callback);
        }
    }

    protected function dropColumn($tableName, $column)
    {
        if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, $column)) {
            DB::statement('ALTER TABLE `'.$tableName.'` DROP COLUMN `'.$column.'`');
        }
    }
}
