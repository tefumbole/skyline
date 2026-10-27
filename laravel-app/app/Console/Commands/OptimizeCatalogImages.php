<?php

namespace App\Console\Commands;

use App\Support\PosProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OptimizeCatalogImages extends Command
{
    protected $signature = 'images:optimize';

    protected $description = 'Resize product and category photos so pages load faster';

    public function handle()
    {
        foreach (['product', 'category'] as $subdir) {
            $stats = PosProductImage::optimizeCatalog($subdir);
            $renamed = $this->rememberRenames($subdir, $stats['renamed']);
            $before = $this->kb($stats['bytes_before']);
            $after = $this->kb($stats['bytes_after']);
            $this->info($subdir.': scanned '.$stats['scanned'].', optimized '.$stats['optimized'].', renamed '.$renamed.', '.$before.' KB -> '.$after.' KB');
        }

        return 0;
    }

    protected function rememberRenames($subdir, array $map)
    {
        if (! $map) {
            return 0;
        }
        $table = $subdir === 'category' ? 'categories' : 'products';
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'image')) {
            return 0;
        }
        $updated = 0;
        foreach (DB::table($table)->whereNotNull('image')->get(['id', 'image']) as $row) {
            $parts = array_map('trim', explode(',', (string) $row->image));
            $changed = false;
            foreach ($parts as $index => $part) {
                $base = basename(str_replace('\\', '/', $part));
                if (isset($map[$base])) {
                    $parts[$index] = $map[$base];
                    $changed = true;
                }
            }
            if ($changed) {
                DB::table($table)->where('id', $row->id)->update([
                    'image' => implode(',', $parts),
                ]);
                $updated++;
            }
        }

        return $updated;
    }

    protected function kb($bytes)
    {
        return (string) (int) round(((int) $bytes) / 1024);
    }
}
