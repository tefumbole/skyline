<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\GroupContactExportService;
use Illuminate\Console\Command;

class DeepResolveWhatsAppGroups extends Command
{
    protected $signature = 'whatsapp:deep-resolve {--rounds=40}';

    protected $description = 'Fetch WhatsApp groups, resolve Cameroon numbers with Campay, and other numbers from WhatsApp or saved names.';

    public function handle()
    {
        @set_time_limit(0);
        $rounds = max(1, (int) $this->option('rounds'));
        $service = app(GroupContactExportService::class);
        for ($i = 1; $i <= $rounds; $i++) {
            $result = $service->deepResolve(4, 20);
            $this->line(
                'Round '.$i
                .': fetched '.$result['fetched']
                .' groups, '.$result['remaining'].' names still open'
                .(! empty($result['busy']) ? ', WhatsApp is busy' : '')
            );
            if ((int) $result['remaining'] < 1 && (int) $result['fetched'] < 1) {
                $this->info('Group names are resolved.');

                return 0;
            }
            sleep(! empty($result['busy']) ? 20 : 2);
        }
        $this->info('Stopped after '.$rounds.' rounds. Run the command again to continue.');

        return 0;
    }
}
