<?php

namespace App\Console\Commands;

use App\Services\Cloud\CloudTenantContextRunner;
use App\Services\Cloud\CloudWhatsAppConnectionResolver;
use App\Services\WhatsApp\GroupContactExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ResolveGroupNameSessions extends Command
{
    protected $signature = 'whatsapp:resolve-group-sessions {--session=5} {--pause=4} {--rest=90}';

    protected $description = 'Resolve Cameroon numbers in the selected WhatsApp groups, a few at a time, so Campay does not lock name lookup.';

    public function handle(GroupContactExportService $groups, CloudWhatsAppConnectionResolver $connections, CloudTenantContextRunner $tenants)
    {
        @set_time_limit(0);
        $tenantId = $connections->soleTenantId();
        if (! $tenantId) {
            $this->error('No company is set for WhatsApp contacts.');

            return 1;
        }
        $tenants->run($tenantId, function () use ($groups) {
            $this->runSessions($groups);
        });

        return 0;
    }

    protected function runSessions(GroupContactExportService $groups)
    {
        $session = max(1, (int) $this->option('session'));
        $pause = max(2, (int) $this->option('pause'));
        $rest = max($pause, (int) $this->option('rest'));
        $jids = $this->selectedGroups($groups);
        if (! $jids) {
            $this->error('None of the selected groups are on this server.');

            return;
        }
        $this->line('Groups: '.count($jids));
        $looked = 0;
        $named = 0;
        $inSession = 0;
        while (true) {
            if ($groups->campayBlocked()) {
                $this->line('Campay name lookup is paused. Waiting for the lock to lift.');
                sleep(300);
                continue;
            }
            Cache::put('campay:holder-session', 1, 180);
            $result = $groups->lookupNextListed($jids);
            Cache::forget('campay:holder-session');
            if (! empty($result['blocked'])) {
                $this->line('Campay locked name lookup. Stopping this session.');
                sleep(300);
                continue;
            }
            if (empty($result['looked'])) {
                $this->info('Selected groups are resolved. Campay lookups '.$looked.', names found '.$named.'.');

                return;
            }
            $looked++;
            $inSession++;
            if (! empty($result['named'])) {
                $named++;
            }
            $this->line('Session lookup '.$inSession.' of '.$session.'. Found '.$named.' names. Still open '.$result['remaining'].'.');
            if ($inSession >= $session) {
                $inSession = 0;
                $this->line('Resting '.$rest.' seconds before the next session.');
                sleep($rest);
                continue;
            }
            sleep($pause);
        }
    }

    protected function selectedGroups(GroupContactExportService $groups)
    {
        $needles = [
            'anyingang global family',
            'arsenal classique',
            'chill on the hill management',
            'naija plug',
            'accessoires by larissa',
            'ccaskesans 1990-1995',
            'celebrating pa lamfu henry',
        ];
        $jids = [];
        foreach ($groups->groupNames() as $jid => $name) {
            $normal = $this->normalName($name);
            foreach ($needles as $needle) {
                if ($normal !== '' && strpos($normal, $this->normalName($needle)) !== false) {
                    $jids[] = $jid;
                    $this->line($name);
                    break;
                }
            }
        }

        return $jids;
    }

    protected function normalName($name)
    {
        $normal = strtolower(str_replace(['—', '–', '-', '&'], ' ', (string) $name));

        return trim(preg_replace('/\s+/', ' ', $normal));
    }
}
