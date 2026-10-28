<?php

namespace App\Console\Commands;

use App\GalleryItem;
use App\Support\GalleryImage;
use Illuminate\Console\Command;

class OptimizeGalleryImages extends Command
{
    protected $signature = 'gallery:optimize';

    protected $description = 'Shrink gallery photos already on disk so the public page loads faster';

    public function handle()
    {
        @ini_set('memory_limit', '512M');

        $items = GalleryItem::query()->where('type', 'image')->whereNotNull('file_path')->get();
        $done = 0;
        $skipped = 0;
        $failed = 0;
        $before = 0;
        $after = 0;

        foreach ($items as $item) {
            $full = public_path($item->file_path);
            $thumb = $item->thumb_path ? public_path($item->thumb_path) : null;
            if (GalleryImage::alreadyFitted($full, $thumb)) {
                $skipped++;
                continue;
            }
            if (! is_file($full)) {
                $this->warn('Missing file for gallery item '.$item->id);
                $failed++;
                continue;
            }

            $dir = dirname($full);
            $oldBytes = (int) filesize($full);
            if ($thumb && is_file($thumb)) {
                $oldBytes += (int) filesize($thumb);
            }
            $oldThumb = ($thumb && is_file($thumb)) ? $thumb : null;
            $pair = GalleryImage::optimizeExisting($full, $dir);
            if (! $pair) {
                $this->warn('Could not shrink gallery item '.$item->id);
                $failed++;
                continue;
            }
            if ($oldThumb && is_file($oldThumb) && realpath($oldThumb) !== realpath(public_path($pair['thumb']))) {
                @unlink($oldThumb);
            }

            $item->file_path = $pair['file'];
            $item->thumb_path = $pair['thumb'];
            $item->save();

            $newBytes = (int) @filesize(public_path($pair['file'])) + (int) @filesize(public_path($pair['thumb']));
            $before += $oldBytes;
            $after += $newBytes;
            $done++;
            $this->line('Shrunk '.$item->id.' ('.$this->kb($oldBytes).' KB -> '.$this->kb($newBytes).' KB)');
        }

        $this->info('Shrunk '.$done.', already fitted '.$skipped.', failed '.$failed.'. '.$this->kb($before).' KB -> '.$this->kb($after).' KB.');

        return 0;
    }

    protected function kb($bytes)
    {
        return (string) (int) round(((int) $bytes) / 1024);
    }
}
