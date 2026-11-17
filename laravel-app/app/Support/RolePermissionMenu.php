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
                ['POS', 'sales-pos'],
                ['Add Sale', 'sales-add'],
                ['Import Sale By CSV', 'sales-import'],
                ['Gift Card List', 'gift_card'],
                ['Coupon List', 'coupon'],
                ['Delivery List', 'delivery-list'],
                ['Delivery Sign Pending', 'delivery-pending'],
                ['Signed Delivery', 'delivery-signed'],
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
                ['Ready for Delivery', 'orders-ready-desk'],
            ]),
            self::section('Call Center', [
                ['Call Center', 'call-center'],
            ]),
            self::section('Quotation', [
                ['Quotation List', 'quotes-index'],
                ['Add Quotation', 'quotes-add'],
            ]),
            self::section('Online Order', [
                ['Order List', 'orders-index'],
                ['Donation List', 'donations-index'],
                ['Service List', 'services-index'],
                ['Online Booking List', 'online-booking-index'],
            ]),
            self::section('Payments', [
                ['Awaiting Payment', 'payments-awaiting'],
                ['Collected', 'payments-collected'],
                ['Payments', 'payments-list'],
                ['Payout', 'payments-payout-tab'],
                ['Request for Payment', 'payments-request'],
            ]),
            self::section('Rental Module', [
                ['Booking Create', 'booking_create'],
                ['Booking List', 'booking-list'],
                ['Booking Request', 'booking-request'],
                ['Booked Products', 'booking-products'],
                ['Booking Reminder', 'booking-reminder'],
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
                ['All Announcements', 'announcements-all'],
                ['Scheduled', 'announcements-scheduled'],
                ['Reminders', 'announcements-reminders'],
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
                ['Task Dashboard', 'tasks-dashboard'],
                ['All Tasks', 'tasks-all'],
                ['Scheduled', 'tasks-scheduled'],
                ['Reminders', 'tasks-reminders'],
                ['Pending Acceptances', 'tasks-pending'],
                ['Create Task', 'tasks.create'],
                ['Task Settings', 'tasks.settings'],
                ['Task Manager', 'tasks_module'],
            ]),
            self::section('TimeSheets (Employee)', [
                ['Create Activity', 'timesheets-activity'],
                ['Fill Time Sheet', 'timesheets-fill'],
                ['Working Week', 'timesheets-week'],
                ['TimeSheets', 'timesheets_module'],
            ]),
            self::section('TimeSheet Admin', [
                ['Reports', 'timesheets-admin-report'],
                ['Overtime Report', 'timesheets-admin-overtime'],
                ['Manage All', 'timesheets-admin-manage'],
                ['Categories', 'timesheets-admin-categories'],
            ]),
            self::section('Purchase', [
                ['Purchase List', 'purchases-index'],
                ['Add Purchase', 'purchases-add'],
                ['Import Purchase By CSV', 'purchases-import'],
            ]),
            self::section('Return', [
                ['Sale', 'returns-index'],
                ['Purchase', 'purchase-return-index'],
            ]),
            self::section('Permissions', [
                ['Awaiting Approval', 'permissions-awaiting'],
                ['Approved Permissions', 'permissions-approved'],
                ['Denied Permissions', 'permissions-denied'],
                ['Permissions Listings', 'permissions-list'],
            ]),
            self::section('Fixed Assets', [
                ['Assets Dashboard', 'asset-dashboard'],
                ['Assets Region', 'region-index'],
                ['Assets Station', 'station-index'],
                ['Assets Donor', 'donor-index'],
                ['Assets Type', 'asset-type-index'],
                ['Add Assets', 'asset-add'],
                ['Assets List', 'asset-index'],
                ['Repair Activity', 'asset-repair'],
                ['Asset Activity', 'asset-activity'],
                ['Asset Expense', 'asset-expense-index'],
                ['Dispose Assets', 'asset-disppose'],
                ['Transfer Assets', 'asset-transfer'],
                ['Asset Sales', 'asset-sale'],
                ['Assets Report', 'fixed_assets_report'],
                ['Fixed Assets', 'fixed_assets'],
            ]),
            self::section('Events', [
                ['Events Dashboard', 'events-dashboard'],
                ['All Events', 'events-all'],
                ['Event Calendar', 'events-calendar'],
                ['Create Event', 'events.create'],
                ['Event Workforce', 'events.manage_workforce'],
                ['Event Timesheets', 'events-timesheets'],
                ['Labour Payments', 'events-payments'],
                ['Event Reminders', 'events-reminders'],
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
                ['Applicants', 'jobs-applicants'],
                ['Job Postings', 'jobs-postings'],
                ['Add Job', 'jobs.create'],
                ['All Applications', 'jobs-applications'],
                ['Awaiting Approval', 'jobs-awaiting'],
                ['Selected', 'jobs-selected'],
                ['Rejected', 'jobs-rejected'],
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
                ['Dashboard', 'contracts-dashboard'],
                ['Contract List', 'contracts-list'],
                ['Awaiting Client Signature', 'contracts-client-sign'],
                ['Awaiting Admin Signature', 'contracts-admin-sign'],
                ['Signed', 'contracts-signed'],
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
                ['Command Center', 'whatsapp-command'],
                ['Conversations', 'whatsapp-conversations'],
                ['Leads', 'whatsapp-leads'],
                ['Groups', 'whatsapp-groups'],
                ['Rentals', 'whatsapp-rentals'],
                ['Attendance', 'whatsapp-attendance'],
                ['Calls', 'whatsapp-calls'],
                ['AI Assistant', 'whatsapp-assistant'],
                ['AI brief', 'whatsapp-brief'],
                ['People I know', 'whatsapp-people'],
                ['Settings', 'whatsapp-settings'],
                ['WhatsApp Hub', 'whatsapp_module'],
            ]),
            self::section('Digital Invitations', [
                ['Categories', 'online_invitation_category'],
                ['Templates', 'online_invitation_template'],
                ['Events', 'online_invitation_event'],
                ['All Invitations', 'invitations-all'],
                ['Awaiting Sending', 'invitations-awaiting'],
                ['Sent', 'invitations-sent'],
                ['Used', 'invitations-used'],
                ['Failed', 'invitations-failed'],
                ['Attending', 'invitations-attending'],
                ['Request Links', 'invitations-request-links'],
                ['Reminders', 'invitations-reminders'],
                ['Queued Messages', 'invitations-queued'],
                ['Create Invitation', 'invitations-create'],
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
                ['Activity Logs', 'activity-logs'],
                ['.env Settings', 'env-setting'],
                ['Mail Setting', 'mail_setting'],
                ['POS Setting', 'pos_setting'],
                ['Reward Point Setting', 'reward_point_setting'],
                ['Backup Database', 'backup_database'],
                ['Empty Database', 'empty_database'],
            ]),
            self::section('Expenses', [
                ['Expenses', 'expenses-index'],
                ['Create expense', 'expenses-add'],
                ['Edit expense', 'expenses-edit'],
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
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (self::names() as $name) {
            try {
                $permission = Permission::findByName($name, 'web');
                $on = $request->has($name);
                if ($on && ! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
                if (! $on && $role->hasPermissionTo($permission)) {
                    $role->revokePermissionTo($permission);
                }
            } catch (\Throwable $e) {
            }
        }
        self::grantWith($role, 'expenses-index', ['expenses-add', 'expenses-edit', 'expenses-delete']);
        self::linkUsers($role);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * A menu tick also grants the actions on that screen.
     */
    protected static function grantWith(Role $role, $source, array $actions)
    {
        $on = $role->hasPermissionTo($source);
        foreach ($actions as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            if ($on && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }

    /**
     * Every person on this role receives the role's permissions.
     */
    public static function linkUsers(Role $role)
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('users') || ! \Illuminate\Support\Facades\Schema::hasTable('model_has_roles')) {
            return;
        }
        $ids = \Illuminate\Support\Facades\DB::table('users')->where('role_id', $role->id)->pluck('id');
        foreach ($ids as $id) {
            $linked = \Illuminate\Support\Facades\DB::table('model_has_roles')
                ->where('role_id', $role->id)
                ->where('model_id', $id)
                ->where('model_type', \App\User::class)
                ->exists();
            if ($linked) {
                continue;
            }
            \Illuminate\Support\Facades\DB::table('model_has_roles')->insert([
                'role_id' => $role->id,
                'model_type' => \App\User::class,
                'model_id' => $id,
            ]);
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
