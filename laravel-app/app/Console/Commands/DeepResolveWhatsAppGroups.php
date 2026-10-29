<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\GroupContactExportService;
use Illuminate\Console\Command;

class DeepResolveWhatsAppGroups extends Command
{
    protected $signature = 'whatsapp:deep-resolve {--rounds=40}';

    protected $description = 'Resolve group numbers to names with Campay.';

    public function handle()
    {
        @set_time_limit(0);
        $rounds = max(1, (int) $this->option('rounds'));
        $service = app(GroupContactExportService::class);
        $service->reopenUnnamedForCampay();
        for ($i = 1; $i <= $rounds; $i++) {
            $result = $service->deepResolve(0, 25);
            $this->line('Round '.$i.': '.$result['remaining'].' names still open');
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
