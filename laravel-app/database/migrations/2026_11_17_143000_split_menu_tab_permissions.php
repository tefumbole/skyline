<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SplitMenuTabPermissions extends Migration
{
    public function up()
    {
        $copies = [
            'delivery' => ['delivery-list', 'delivery-pending', 'delivery-signed'],
            'booking_index' => ['booking-list', 'booking-request', 'booking-products', 'booking-reminder', 'online-booking-index'],
            'orders-ready-index' => ['orders-ready-desk'],
            'purchases-add' => ['purchases-import'],
            'announcements_module' => ['announcements.compose', 'announcements-all', 'announcements-scheduled', 'announcements-reminders', 'announcements.templates', 'announcements.categories', 'announcements.settings'],
            'announcements.view' => ['announcements-all', 'announcements-scheduled', 'announcements-reminders'],
            'tasks.view' => ['tasks-dashboard', 'tasks-all', 'tasks-scheduled', 'tasks-reminders', 'tasks-pending'],
            'timesheets_module' => ['timesheets-activity', 'timesheets-fill', 'timesheets-week'],
            'timesheets.employee' => ['timesheets-activity', 'timesheets-fill', 'timesheets-week'],
            'timesheets.admin' => ['timesheets-admin-report', 'timesheets-admin-overtime', 'timesheets-admin-manage', 'timesheets-admin-categories'],
            'timesheets.manage' => ['timesheets-admin-report', 'timesheets-admin-overtime', 'timesheets-admin-manage', 'timesheets-admin-categories'],
            'permissions_module' => ['permissions-awaiting', 'permissions-approved', 'permissions-denied', 'permissions-list'],
            'hrm' => ['permissions-awaiting', 'permissions-approved', 'permissions-denied', 'permissions-list'],
            'asset-index' => ['asset-dashboard'],
            'activity-index' => ['asset-repair', 'asset-activity'],
            'events.view' => ['events-dashboard', 'events-all', 'events-calendar', 'events-timesheets', 'events-payments', 'events-reminders'],
            'jobs_module' => ['jobs-applicants', 'jobs-postings', 'jobs-applications', 'jobs-awaiting', 'jobs-selected', 'jobs-rejected'],
            'jobs.view' => ['jobs-postings', 'jobs-applications', 'jobs-awaiting', 'jobs-selected', 'jobs-rejected'],
            'contracts_module' => ['contracts-dashboard', 'contracts-list', 'contracts-client-sign', 'contracts-admin-sign', 'contracts-signed', 'contracts.create', 'contracts.templates', 'contracts.clauses', 'contracts.report', 'contracts.settings'],
            'contracts.view' => ['contracts-dashboard', 'contracts-list', 'contracts-client-sign', 'contracts-admin-sign', 'contracts-signed'],
            'whatsapp_module' => ['whatsapp-command', 'whatsapp-conversations', 'whatsapp-leads', 'whatsapp-groups', 'whatsapp-rentals', 'whatsapp-attendance', 'whatsapp-calls', 'whatsapp-assistant', 'whatsapp-brief', 'whatsapp-people', 'whatsapp-settings'],
            'whatsapp.view' => ['whatsapp-command', 'whatsapp-conversations', 'whatsapp-leads', 'whatsapp-rentals', 'whatsapp-attendance', 'whatsapp-calls', 'whatsapp-brief', 'whatsapp-people'],
            'whatsapp.manage' => ['whatsapp-groups', 'whatsapp-assistant', 'whatsapp-settings'],
            'payments-index' => ['payments-awaiting', 'payments-collected', 'payments-list', 'payments-payout-tab', 'payments-request'],
            'online_invitation_send_invitation' => ['invitations-all', 'invitations-awaiting', 'invitations-sent', 'invitations-used', 'invitations-failed', 'invitations-attending', 'invitations-request-links', 'invitations-reminders', 'invitations-queued', 'invitations-create'],
            'invitations.view' => ['invitations-all', 'invitations-awaiting', 'invitations-sent', 'invitations-used', 'invitations-failed', 'invitations-attending', 'invitations-request-links', 'invitations-reminders', 'invitations-queued', 'invitations-create'],
            'general_setting' => ['activity-logs', 'env-setting'],
        ];
        foreach ($copies as $source => $targets) {
            $this->copy($source, $targets);
        }

        $everyone = ['dashboard', 'call-center'];
        $roleIds = DB::table('roles')->where('is_active', 1)->pluck('id');
        foreach ($everyone as $name) {
            $this->grant($name, $roleIds);
        }
        $helpRoles = DB::table('roles')->where('is_active', 1)->whereNotIn('name', ['Restaurant', 'Kitchen', 'restaurant', 'kitchen'])->pluck('id');
        $this->grant('help', $helpRoles);
        $this->grant('timesheets-admin-report', [2]);
        $this->grant('timesheets-admin-overtime', [2]);
        $this->grant('timesheets-admin-manage', [2]);
        $this->grant('timesheets-admin-categories', [2]);
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
            $this->grant($name, $roleIds);
        }
    }

    protected function grant($name, $roleIds)
    {
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
