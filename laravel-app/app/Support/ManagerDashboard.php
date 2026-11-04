<?php

namespace App\Support;

use App\Services\Restaurant\RestaurantAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Charts for the Manager home. Each chart is one menu that role can open.
 */
class ManagerDashboard
{
    public static function charts($user)
    {
        $charts = [];
        $year = (int) date('Y');
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        if (self::can($user, 'sales-index') || self::can($user, 'sales-add')) {
            $charts[] = self::monthly('mgr-sales', 'Sales', 'Sold this year, FCFA', 'sales', 'grand_total', $year, $months, true);
        }
        if (self::can($user, 'payments-index')) {
            $charts[] = self::monthly('mgr-payments', 'Payments', 'Received this year, FCFA', 'payments', 'amount', $year, $months, true);
        }
        if (self::can($user, 'payments-payout')) {
            $charts[] = self::grouped('mgr-payouts', 'Payouts', 'Payment requests', 'campay_payout_requests', 'status', [], 'doughnut');
        }
        if (self::can($user, 'products-index')) {
            $charts[] = self::topProducts($year);
        }
        if (self::can($user, 'orders-index')) {
            $charts[] = self::grouped('mgr-orders', 'Orders', 'Items by status', 'restaurant_order_items', 'status', [
                'awaiting' => 'Waiting',
                'preparing' => 'Being prepared',
                'ready' => 'Ready for delivery',
                'delivered' => 'Delivered',
            ], 'doughnut');
        }
        if (self::can($user, 'tables-index')) {
            $charts[] = self::tables();
        }
        if (self::can($user, 'customers-index')) {
            $charts[] = self::monthly('mgr-customers', 'Customers', 'New customers this year', 'customers', null, $year, $months, false);
        }
        if (self::can($user, 'quotes-index')) {
            $charts[] = self::grouped('mgr-quotes', 'Quotations', 'By status', 'quotations', 'quotation_status', [
                '1' => 'Pending',
                '2' => 'Awaiting signature',
                '3' => 'Approved',
                '4' => 'Rejected',
                '5' => 'No signature',
                '6' => 'Client quote',
            ], 'doughnut');
        }
        if (self::can($user, 'tasks_module') || self::can($user, 'tasks.view')) {
            $charts[] = self::grouped('mgr-tasks', 'Tasks', 'By status', 'tasks', 'status', [], 'doughnut');
        }
        if (self::can($user, 'letter_index') || self::can($user, 'letter_module')) {
            $charts[] = self::letters();
        }
        if (self::can($user, 'announcements_module')) {
            $charts[] = self::announcements();
        }
        if (self::can($user, 'timesheets.admin') || self::can($user, 'timesheets.manage') || self::can($user, 'timesheets.employee')) {
            $charts[] = self::timesheets();
        }

        return array_values(array_filter($charts));
    }

    protected static function can($user, $name)
    {
        return RestaurantAccess::can($user, $name);
    }

    protected static function monthly($id, $title, $hint, $table, $sumColumn, $year, array $months, $money)
    {
        if (! Schema::hasTable($table)) {
            return null;
        }
        $values = array_fill(0, 12, 0);
        try {
            $select = $sumColumn
                ? 'MONTH(created_at) as m, SUM('.$sumColumn.') as total'
                : 'MONTH(created_at) as m, COUNT(*) as total';
            $rows = DB::table($table)
                ->selectRaw($select)
                ->whereYear('created_at', $year)
                ->groupBy('m')
                ->get();
            foreach ($rows as $row) {
                $index = (int) $row->m - 1;
                if ($index >= 0 && $index < 12) {
                    $values[$index] = $money ? round((float) $row->total, 2) : (int) $row->total;
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        return self::chart($id, $title, $hint, 'bar', $months, $values, $money);
    }

    protected static function topProducts($year)
    {
        if (! Schema::hasTable('product_sales') || ! Schema::hasTable('products')) {
            return null;
        }
        try {
            $rows = DB::table('product_sales')
                ->join('products', 'products.id', '=', 'product_sales.product_id')
                ->selectRaw('products.name as name, SUM(product_sales.qty) as qty')
                ->whereYear('product_sales.created_at', $year)
                ->whereMonth('product_sales.created_at', date('n'))
                ->groupBy('products.id', 'products.name')
                ->orderBy('qty', 'desc')
                ->limit(5)
                ->get();
        } catch (\Throwable $e) {
            return null;
        }
        $labels = [];
        $values = [];
        foreach ($rows as $row) {
            $labels[] = (string) $row->name;
            $values[] = round((float) $row->qty, 2);
        }
        if ($labels === []) {
            $labels = ['None yet'];
            $values = [0];
        }

        return self::chart('mgr-products', 'Products', 'Best sellers this month', 'bar', $labels, $values, false);
    }

    protected static function tables()
    {
        try {
            $pending = 0;
            $paid = 0;
            foreach (TableBoard::rows() as $row) {
                if (($row['status'] ?? '') === 'Paid') {
                    $paid++;
                } else {
                    $pending++;
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        return self::chart('mgr-tables', 'Tables', 'Open bills', 'doughnut', ['Pending', 'Paid'], [$pending, $paid], false);
    }

    protected static function letters()
    {
        if (! Schema::hasTable('letters') || ! Schema::hasColumn('letters', 'is_rejected')) {
            return null;
        }
        try {
            $rejected = (int) DB::table('letters')->where('is_rejected', 1)->count();
            $valid = (int) DB::table('letters')->where(function ($query) {
                $query->where('is_rejected', 0)->orWhereNull('is_rejected');
            })->count();
        } catch (\Throwable $e) {
            return null;
        }

        return self::chart('mgr-letters', 'Letters', 'Valid and rejected', 'doughnut', ['Valid', 'Not valid'], [$valid, $rejected], false);
    }

    protected static function announcements()
    {
        if (! Schema::hasTable('announcements') || ! Schema::hasColumn('announcements', 'status')) {
            return null;
        }
        try {
            $rows = DB::table('announcements')
                ->selectRaw('status, COUNT(*) as total')
                ->where('status', '!=', 'deleted')
                ->groupBy('status')
                ->get();
        } catch (\Throwable $e) {
            return null;
        }
        $labels = [];
        $values = [];
        foreach ($rows as $row) {
            $labels[] = ucfirst((string) $row->status);
            $values[] = (int) $row->total;
        }

        return self::chart('mgr-announcements', 'Announcements', 'By status', 'doughnut', $labels, $values, false);
    }

    protected static function timesheets()
    {
        if (! Schema::hasTable('be_timesheet_entries') || ! Schema::hasColumn('be_timesheet_entries', 'status')) {
            return null;
        }
        try {
            $rows = DB::table('be_timesheet_entries')
                ->selectRaw('status, SUM(hours) as total')
                ->whereYear('entry_date', date('Y'))
                ->whereMonth('entry_date', date('n'))
                ->groupBy('status')
                ->get();
        } catch (\Throwable $e) {
            return null;
        }
        $labels = [];
        $values = [];
        foreach ($rows as $row) {
            $labels[] = ucfirst((string) ($row->status ?: 'Open'));
            $values[] = round((float) $row->total, 2);
        }

        return self::chart('mgr-timesheets', 'Timesheets', 'Hours this month', 'doughnut', $labels, $values, false);
    }

    protected static function grouped($id, $title, $hint, $table, $column, array $labels, $type)
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return null;
        }
        try {
            $rows = DB::table($table)
                ->selectRaw($column.' as label, COUNT(*) as total')
                ->groupBy($column)
                ->get();
        } catch (\Throwable $e) {
            return null;
        }
        $names = [];
        $values = [];
        foreach ($rows as $row) {
            $key = (string) $row->label;
            $names[] = $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
            $values[] = (int) $row->total;
        }

        return self::chart($id, $title, $hint, $type, $names, $values, false);
    }

    protected static function chart($id, $title, $hint, $type, array $labels, array $values, $money)
    {
        if ($labels === []) {
            $labels = ['None yet'];
            $values = [0];
        }
        $palette = ['#0c1830', '#e1b368', '#7b61ff', '#f59e0b', '#14b8a6', '#e11d48', '#64748b', '#0b3f90'];
        $colors = [];
        foreach ($labels as $index => $label) {
            $colors[] = $palette[$index % count($palette)];
        }

        return [
            'id' => $id,
            'title' => $title,
            'hint' => $hint,
            'type' => $type,
            'labels' => array_values($labels),
            'values' => array_values($values),
            'colors' => $colors,
            'money' => $money,
        ];
    }
}
