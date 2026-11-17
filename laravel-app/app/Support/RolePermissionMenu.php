<?php

namespace App\Support;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionMenu
{
    public static function sections()
    {
        return [
            self::section('Dashboard', [
                ['Dashboard', 'dashboard'],
            ]),
            self::section('Sale', [
                ['Sale List', 'sales-index'],
                ['POS', 'sales-add'],
                ['Add Sale', 'sales-add'],
                ['Import Sale By CSV', 'sales-add'],
                ['Gift Card List', 'gift_card'],
                ['Coupon List', 'coupon'],
                ['Delivery List', 'delivery'],
                ['Delivery Sign Pending', 'delivery'],
                ['Signed Delivery', 'delivery'],
            ]),
            self::section('Tables', [
                ['Tables', 'tables'],
            ]),
            self::section('Orders', [
                ['Restaurant', 'kitchen-orders'],
                ['Bar', 'bar-orders'],
                ['Online orders', 'orders-online-index'],
                ['Awaiting', 'orders-awaiting-index'],
                ['Accepted', 'orders-accepted-index'],
                ['Ready for Delivery', 'orders-ready-index'],
                ['On Hold', 'orders-hold-index'],
                ['Delivered', 'orders-delivered-index'],
                ['Log', 'order-logs'],
            ]),
            self::section('Ready for Delivery', [
                ['Ready for Delivery', 'orders-ready-index'],
            ]),
            self::section('Call Center', [
                ['Call Center', 'call-center'],
            ]),
            self::section('Quotation', [
                ['Quotation List', 'quotes-index'],
                ['Add Quotation', 'quotes-add'],
            ]),
            self::section('Rental Module', [
                ['Booking Create', 'booking_create'],
                ['Booking List', 'booking_index'],
                ['Booking Request', 'booking_index'],
                ['Booked Products', 'booking_index'],
                ['Booking Reminder', 'booking_index'],
                ['Awaiting Signature', 'booking_awaiting_signature'],
                ['Pending Review', 'booking_pending_review'],
                ['Signed Contracts', 'booking_signed_contracts'],
                ['Booking Calendar', 'booking_report'],
                ['Rental Module', 'booking_module'],
            ]),
            self::section('Product', [
                ['Product List', 'products-index'],
                ['Add Product', 'products-add'],
                ['Category', 'category'],
                ['Print Barcode', 'print_barcode'],
                ['Adjustment List', 'adjustment'],
                ['Stock Count', 'stock_count'],
            ]),
            self::section('Announcements', [
                ['Announcements', 'announcements_module'],
                ['Compose', 'announcements.compose'],
                ['All Announcements', 'announcements.view'],
                ['Scheduled', 'announcements.view'],
                ['Reminders', 'announcements.view'],
                ['Templates', 'announcements.templates'],
                ['Categories', 'announcements.categories'],
                ['Create SMS', 'create_sms'],
                ['SMS Setting', 'sms_setting'],
                ['Settings', 'announcements.settings'],
            ]),
            self::section('Letters', [
                ['Letter Module', 'letter_module'],
                ['Letter Categories', 'letter_category'],
                ['Letter Listing', 'letter_index'],
                ['Create Letter', 'letter_create'],
                ['Edit', 'letter_edit'],
                ['Delete', 'letter_delete'],
                ['Rejected Letters', 'letter_rejected'],
                ['Awaiting Approve', 'letter_approve_index'],
                ['Approve', 'letter_approve'],
                ['Letter Templates', 'letter_template'],
            ]),
            self::section('COTH Reservations', [
                ['COTH Reservations', 'coth-reservations'],
            ]),
            self::section('Help', [
                ['Help', 'help'],
            ]),
            self::section('Task Manager', [
                ['Task Dashboard', 'tasks.view'],
                ['All Tasks', 'tasks.view'],
                ['Scheduled', 'tasks.view'],
                ['Reminders', 'tasks.view'],
                ['Pending Acceptances', 'tasks.view'],
                ['Create Task', 'tasks.create'],
                ['Task Settings', 'tasks.settings'],
                ['Task Manager', 'tasks_module'],
            ]),
            self::section('TimeSheets (Employee)', [
                ['Create Activity', 'timesheets.employee'],
                ['Fill Time Sheet', 'timesheets.employee'],
                ['Working Week', 'timesheets.employee'],
                ['TimeSheets', 'timesheets_module'],
            ]),
            self::section('Purchase', [
                ['Purchase List', 'purchases-index'],
                ['Add Purchase', 'purchases-add'],
                ['Import Purchase By CSV', 'purchases-add'],
            ]),
            self::section('Return', [
                ['Sale', 'returns-index'],
                ['Purchase', 'purchase-return-index'],
            ]),
            self::section('Permissions', [
                ['Awaiting Approval', 'permissions_module'],
                ['Approved Permissions', 'permissions_module'],
                ['Denied Permissions', 'permissions_module'],
                ['Permissions Listings', 'permissions_module'],
            ]),
            self::section('Fixed Assets', [
                ['Assets Dashboard', 'asset-index'],
                ['Assets Region', 'region-index'],
                ['Assets Station', 'station-index'],
                ['Assets Donor', 'donor-index'],
                ['Assets Type', 'asset-type-index'],
                ['Add Assets', 'asset-add'],
                ['Assets List', 'asset-index'],
                ['Repair Activity', 'activity-index'],
                ['Asset Activity', 'activity-index'],
                ['Asset Expense', 'asset-expense-index'],
                ['Dispose Assets', 'asset-disppose'],
                ['Transfer Assets', 'asset-transfer'],
                ['Asset Sales', 'asset-sale'],
                ['Assets Report', 'fixed_assets_report'],
                ['Fixed Assets', 'fixed_assets'],
            ]),
            self::section('Events', [
                ['Events Dashboard', 'events.view'],
                ['All Events', 'events.view'],
                ['Event Calendar', 'events.view'],
                ['Create Event', 'events.create'],
                ['Event Workforce', 'events.manage_workforce'],
                ['Event Timesheets', 'events.view'],
                ['Labour Payments', 'events.view'],
                ['Event Reminders', 'events.view'],
                ['Contract Templates', 'event_contracts.view'],
                ['Event Settings', 'events.settings'],
                ['Events', 'events_module'],
            ]),
            self::section('HRM', [
                ['Department', 'department'],
                ['Employee', 'employees-index'],
                ['Attendance', 'attendance'],
                ['Payroll', 'payroll'],
                ['Holiday', 'holiday'],
                ['HRM Setting', 'hrm_setting'],
                ['HRM', 'hrm'],
            ]),
            self::section('Job Board', [
                ['Applicants', 'jobs_module'],
                ['Job Postings', 'jobs.view'],
                ['Add Job', 'jobs.create'],
                ['All Applications', 'jobs.view'],
                ['Awaiting Approval', 'jobs.view'],
                ['Selected', 'jobs.view'],
                ['Rejected', 'jobs.view'],
            ]),
            self::section('Accounting', [
                ['Account', 'account-index'],
                ['Money Transfer', 'money-transfer'],
                ['Balance Sheet', 'balance-sheet'],
                ['Account Statement', 'account-statement'],
            ]),
            self::section('About Us Leaders', [
                ['About Us Leaders', 'leaders'],
            ]),
            self::section('People', [
                ['Customer List', 'customers-index'],
                ['Add Customer', 'customers-add'],
                ['User List', 'users-index'],
                ['Add User', 'users-add'],
                ['Biller List', 'billers-index'],
                ['Supplier List', 'suppliers-index'],
            ]),
            self::section('Gallery', [
                ['Gallery', 'gallery'],
            ]),
            self::section('Menu', [
                ['Menu', 'menu-photos'],
            ]),
            self::section('Reports', [
                ['Summary Report', 'profit_loss'],
                ['Best Seller', 'best_seller'],
                ['Daily Sale', 'daily_sale'],
                ['Monthly Sale', 'monthly_sale'],
                ['Daily Purchase', 'daily_purchase'],
                ['Monthly Purchase', 'monthly_purchase'],
                ['Product Report', 'product_report'],
                ['Payment Report', 'payment_report'],
                ['Purchase Report', 'purchase'],
                ['Sale Report', 'sale_report'],
                ['Warehouse Report', 'warehouse_report'],
                ['Warehouse Stock Chart', 'warehouse_stock'],
                ['Product Quantity Alert', 'qty_alert'],
                ['User Report', 'user_report'],
                ['Customer Report', 'customer_report'],
                ['Supplier Report', 'supplier_report'],
                ['Due Report', 'due_report'],
            ]),
            self::section('Contracts', [
                ['Dashboard', 'contracts.view'],
                ['Contract List', 'contracts.view'],
                ['Awaiting Client Signature', 'contracts.view'],
                ['Awaiting Admin Signature', 'contracts.view'],
                ['Signed', 'contracts.view'],
                ['Create Contract', 'contracts.create'],
                ['Templates', 'contracts.templates'],
                ['Clause Library', 'contracts.clauses'],
                ['Reports', 'contracts.report'],
                ['Settings', 'contracts.settings'],
                ['Contracts', 'contracts_module'],
            ]),
            self::section('Site Content', [
                ['Site Content', 'site-content'],
            ]),
            self::section('MAI Voice', [
                ['MAI Voice', 'mai-voice'],
            ]),
            self::section('WhatsApp Hub', [
                ['Command Center', 'whatsapp.view'],
                ['Conversations', 'whatsapp.view'],
                ['Leads', 'whatsapp.view'],
                ['Groups', 'whatsapp.manage'],
                ['Rentals', 'whatsapp.view'],
                ['Attendance', 'whatsapp.view'],
                ['Calls', 'whatsapp.view'],
                ['AI Assistant', 'whatsapp.manage'],
                ['AI brief', 'whatsapp.view'],
                ['People I know', 'whatsapp.view'],
                ['Settings', 'whatsapp.manage'],
                ['WhatsApp Hub', 'whatsapp_module'],
            ]),
            self::section('Guest Reviews', [
                ['Guest Reviews', 'guest-reviews'],
            ]),
            self::section('Settings', [
                ['Send Notification', 'send_notification'],
                ['Warehouse', 'warehouse'],
                ['Customer Group', 'customer_group'],
                ['Brand', 'brand'],
                ['Unit', 'unit'],
                ['Currency', 'currency'],
                ['Tax', 'tax'],
                ['General Setting', 'general_setting'],
                ['Mail Setting', 'mail_setting'],
                ['POS Setting', 'pos_setting'],
                ['Reward Point Setting', 'reward_point_setting'],
                ['Backup Database', 'backup_database'],
                ['Empty Database', 'empty_database'],
            ]),
            self::section('Expenses', [
                ['Expenses', 'expenses-index'],
                ['Categories & budgets', 'expense_categories'],
            ]),
        ];
    }

    public static function names()
    {
        $names = [];
        foreach (self::sections() as $section) {
            foreach ($section['items'] as $item) {
                $names[$item['name']] = true;
            }
        }

        return array_keys($names);
    }

    public static function apply(Role $role, Request $request)
    {
        foreach (self::names() as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $on = $request->has($name);
            try {
                if ($on && ! $role->hasPermissionTo($name)) {
                    $role->givePermissionTo($permission);
                }
                if (! $on && $role->hasPermissionTo($name)) {
                    $role->revokePermissionTo($name);
                }
            } catch (\Throwable $e) {
            }
        }
    }

    protected static function section($title, array $pairs)
    {
        $items = [];
        foreach ($pairs as $pair) {
            $items[] = ['label' => $pair[0], 'name' => $pair[1]];
        }

        return ['title' => $title, 'items' => $items];
    }
}
