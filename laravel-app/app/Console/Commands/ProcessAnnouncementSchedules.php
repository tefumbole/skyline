<?php

namespace App\Console\Commands;

use App\Services\AnnouncementService;
use App\Services\Cloud\CloudTenantContext;
use App\Services\Cloud\CloudTenantContextRunner;
use App\Services\Cloud\CloudWhatsAppConnectionResolver;
use Illuminate\Console\Command;

class ProcessAnnouncementSchedules extends Command
{
    protected $signature = 'announcements:process';
    protected $description = 'Send scheduled WhatsApp announcements and due reminders';

    public function handle(AnnouncementService $service)
    {
        $work = function () use ($service) {
            $sent = $service->processScheduledSends();
            $reminders = $service->processReminders();
            $this->info("Scheduled sends: {$sent}; reminders: {$reminders}");
        };

        if (app(CloudTenantContext::class)->tenant()) {
            $work();

            return 0;
        }

        $tenantId = app(CloudWhatsAppConnectionResolver::class)->soleTenantId();
        if ($tenantId) {
            app(CloudTenantContextRunner::class)->run($tenantId, $work);

            return 0;
        }

        $work();

        return 0;
    }
}
