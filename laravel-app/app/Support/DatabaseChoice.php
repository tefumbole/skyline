<?php

namespace App\Support;

class DatabaseChoice
{
    public static function backupGroups()
    {
        return [
            'sales' => ['Sales and payments', ['sales', 'product_sales', 'payments', 'payment_with_cheque', 'payment_with_credit_card', 'returns', 'product_returns', 'deliveries', 'coupons', 'gift_cards', 'gift_card_recharges']],
            'purchases' => ['Purchases', ['purchases', 'product_purchases', 'return_purchases', 'purchase_product_return']],
            'products' => ['Products and stock', ['products', 'product_variants', 'product_warehouse', 'categories', 'brands', 'units', 'taxes', 'adjustments', 'product_adjustments', 'stock_counts']],
            'people' => ['People and staff', ['customers', 'customer_groups', 'users', 'billers', 'suppliers', 'employees']],
            'accounting' => ['Accounting and expenses', ['accounts', 'money_transfers', 'expenses', 'expense_categories']],
            'orders' => ['Restaurant orders', ['restaurant_orders', 'restaurant_order_items']],
            'quotations' => ['Quotations', ['quotations', 'product_quotations']],
            'settings' => ['Settings and roles', ['general_settings', 'pos_setting', 'hrm_settings', 'reward_point_settings', 'roles', 'permissions', 'role_has_permissions']],
            'other' => ['Everything else', []],
        ];
    }

    public static function emptyGroups()
    {
        return [
            'sales' => ['Sales and payments', ['sales', 'product_sales', 'payments', 'payment_with_cheque', 'payment_with_credit_card', 'returns', 'product_returns', 'deliveries', 'coupons', 'gift_cards', 'gift_card_recharges']],
            'purchases' => ['Purchases', ['purchases', 'product_purchases', 'return_purchases', 'purchase_product_return']],
            'products' => ['Products and stock', ['products', 'product_variants', 'product_warehouse', 'adjustments', 'product_adjustments', 'stock_counts']],
            'customers' => ['Customers', ['customers']],
            'expenses' => ['Expenses', ['expenses']],
            'orders' => ['Restaurant orders', ['restaurant_orders', 'restaurant_order_items']],
            'quotations' => ['Quotations', ['quotations', 'product_quotations']],
            'transfers' => ['Transfers', ['transfers', 'product_transfer', 'product_transfers']],
        ];
    }

    public static function selected($keys, array $existing, $mode)
    {
        $keys = is_array($keys) ? $keys : [];
        $groups = $mode === 'empty' ? self::emptyGroups() : self::backupGroups();
        $named = [];
        $chosen = [];
        foreach ($groups as $key => $group) {
            foreach ($group[1] as $table) {
                $named[$table] = true;
            }
            if (! in_array($key, $keys, true) || $key === 'other') {
                continue;
            }
            foreach ($group[1] as $table) {
                if (in_array($table, $existing, true)) {
                    $chosen[$table] = true;
                }
            }
        }
        if ($mode !== 'empty' && in_array('other', $keys, true)) {
            foreach ($existing as $table) {
                if (! isset($named[$table])) {
                    $chosen[$table] = true;
                }
            }
        }
        if ($mode === 'empty') {
            foreach (self::neverEmpty() as $table) {
                unset($chosen[$table]);
            }
        }
        $safe = [];
        foreach (array_keys($chosen) as $table) {
            if (preg_match('/^[A-Za-z0-9_]+$/', $table)) {
                $safe[] = $table;
            }
        }

        return $safe;
    }

    public static function neverEmpty()
    {
        return [
            'users', 'roles', 'permissions', 'role_has_permissions', 'model_has_roles', 'model_has_permissions',
            'migrations', 'password_resets', 'sessions', 'jobs', 'failed_jobs', 'cache', 'cache_locks',
            'general_settings', 'pos_setting', 'hrm_settings', 'reward_point_settings',
            'warehouses', 'accounts', 'currencies', 'taxes', 'units', 'brands', 'categories',
            'restaurant_tables', 'personal_access_tokens',
        ];
    }
}
