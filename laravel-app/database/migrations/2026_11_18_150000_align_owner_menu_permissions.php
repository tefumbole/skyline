<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AlignOwnerMenuPermissions extends Migration
{
    public function up()
    {
        $copies = [
            'profit_loss' => ['profit-loss'],
            'best_seller' => ['best-seller'],
            'daily_sale' => ['daily-sale'],
            'monthly_sale' => ['monthly-sale'],
            'daily_purchase' => ['daily-purchase'],
            'monthly_purchase' => ['monthly-purchase'],
            'product_report' => ['product-report'],
            'payment_report' => ['payment-report'],
            'purchase' => ['purchase-report'],
            'sale_report' => ['sale-report'],
            'warehouse_report' => ['warehouse-report'],
            'warehouse_stock' => ['warehouse-stock-report'],
            'qty_alert' => ['product-qty-alert'],
            'user_report' => ['user-report'],
            'customer_report' => ['customer-report'],
            'supplier_report' => ['supplier-report'],
            'due_report' => ['due-report'],
        ];
        foreach ($copies as $source => $targets) {
            $this->copy($source, $targets);
        }
        $this->grant([
            'payments-index',
            'payments-payout',
            'payments-awaiting',
            'payments-collected',
            'payments-list',
            'payments-payout-tab',
            'payments-request',
        ], [11]);
    }

    public function down()
    {
    }

    protected function copy($source, array $targets)
    {
        $sourceId = DB::table('permissions')->where('name', $source)->where('guard_name', 'web')->value('id');
        if (! $sourceId) {
            return;
        }
        $roleIds = DB::table('role_has_permissions')->where('permission_id', $sourceId)->pluck('role_id');
        foreach ($targets as $name) {
            $this->grant([$name], $roleIds);
        }
    }

    protected function grant(array $names, $roleIds)
    {
        foreach ($names as $name) {
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
}
