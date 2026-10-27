<?php

namespace App\Console\Commands;

use App\Services\Coth\CothReservationService;
use Illuminate\Console\Command;

class ReleaseCothHolds extends Command
{
    protected $signature = 'coth:release-holds';

    protected $description = 'Release unpaid Chill on the Hill table holds that have expired';

    public function handle(CothReservationService $reservations)
    {
        $count = $reservations->releaseExpired();
        $this->info('Released '.$count.' holds.');

        return 0;
    }
}
