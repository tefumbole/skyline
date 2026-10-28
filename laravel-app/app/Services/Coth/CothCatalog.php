<?php

namespace App\Services\Coth;

use App\CothSection;
use App\CothSetting;
use App\CothSlot;
use App\CothTable;
use App\Event;
use App\EventPublication;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class CothCatalog
{
    public function sections()
    {
        return [
            ['code' => 'yard', 'name' => 'Premium — Yard', 'prefix' => 'YARD', 'rows' => [
                ['capacity' => 4, 'count' => 4, 'price' => 500000, 'style' => 'high_table', 'city' => 1],
                ['capacity' => 6, 'count' => 1, 'price' => 600000, 'style' => 'couch'],
                ['capacity' => 6, 'count' => 5, 'price' => 600000, 'style' => 'high_table', 'city' => 1],
                ['capacity' => 8, 'count' => 2, 'price' => 800000, 'style' => 'couch'],
                ['capacity' => 10, 'count' => 2, 'price' => 1000000, 'style' => 'couch'],
            ]],
            ['code' => 'outer', 'name' => 'Premium — Outer Restaurant', 'prefix' => 'OUTER', 'rows' => [
                ['capacity' => 4, 'count' => 4, 'price' => 500000, 'style' => 'standard'],
                ['capacity' => 6, 'count' => 1, 'price' => 600000, 'style' => 'couch'],
                ['capacity' => 8, 'count' => 2, 'price' => 800000, 'style' => 'couch'],
                ['capacity' => 10, 'count' => 1, 'price' => 1000000, 'style' => 'couch'],
            ]],
            ['code' => 'hall', 'name' => 'Gold — Hall', 'prefix' => 'HALL', 'rows' => [
                ['capacity' => 4, 'count' => 2, 'price' => 300000, 'style' => 'couch'],
                ['capacity' => 6, 'count' => 5, 'price' => 500000, 'style' => 'couch'],
                ['capacity' => 8, 'count' => 3, 'price' => 600000, 'style' => 'couch'],
                ['capacity' => 10, 'count' => 2, 'price' => 800000, 'style' => 'couch'],
            ]],
            ['code' => 'pool', 'name' => 'Silver — Pool', 'prefix' => 'POOL', 'rows' => [
                ['capacity' => 2, 'count' => 2, 'price' => 150000, 'style' => 'high_table'],
                ['capacity' => 3, 'count' => 1, 'price' => 250000, 'style' => 'high_table'],
                ['capacity' => 4, 'count' => 5, 'price' => 350000, 'style' => 'high_table'],
                ['capacity' => 6, 'count' => 1, 'price' => 500000, 'style' => 'couch'],
            ]],
        ];
    }

    public function eventDates()
    {
        $closed = ['2026-12-28', '2026-12-29'];
        $dates = [];
        $cursor = Carbon::parse('2026-12-24');
        $end = Carbon::parse('2027-01-02');
        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();
            if (! in_array($date, $closed, true)) {
                $dates[] = $date;
            }
            $cursor->addDay();
        }

        return $dates;
    }

    public function discountDates()
    {
        return [];
    }

    public function seed()
    {
        if (! Schema::hasTable('coth_settings') || CothTable::query()->exists()) {
            $this->seedCategoryAndEvent();

            return;
        }

        $settings = CothSetting::create([
            'deposit_percent' => 20,
            'discount_percent' => 0,
            'hold_minutes' => 15,
            'discount_dates' => json_encode($this->discountDates()),
            'timezone' => 'Africa/Douala',
        ]);

        $sort = 0;
        foreach ($this->sections() as $sectionDef) {
            $section = CothSection::create([
                'code' => $sectionDef['code'],
                'name' => $sectionDef['name'],
                'sort' => $sort++,
                'is_active' => 1,
            ]);
            $seen = [];
            foreach ($sectionDef['rows'] as $row) {
                $style = $row['style'];
                for ($i = 1; $i <= $row['count']; $i++) {
                    $seen[$row['capacity']] = ($seen[$row['capacity']] ?? 0) + 1;
                    $n = $seen[$row['capacity']];
                    $code = $sectionDef['prefix'].'-T'.$row['capacity'].'-'.str_pad((string) $n, 2, '0', STR_PAD_LEFT);
                    $attrs = [
                        'coth_section_id' => $section->id,
                        'code' => $code,
                        'name' => $code,
                        'capacity' => $row['capacity'],
                        'seating_style' => $style,
                        'price' => $row['price'],
                        'is_active' => 1,
                    ];
                    if (Schema::hasColumn('coth_tables', 'visual_preset')) {
                        $attrs['visual_preset'] = $this->presetSlug($style, $n);
                        $attrs['is_high_table'] = $style === 'high_table' ? 1 : 0;
                        $attrs['is_city_view'] = ! empty($row['city']) ? 1 : 0;
                    }
                    CothTable::create($attrs);
                }
            }
        }

        $tableIds = CothTable::query()->pluck('id');
        $now = now();
        $slotRows = [];
        foreach ($tableIds as $tableId) {
            foreach ($this->eventDates() as $date) {
                $slotRows[] = [
                    'coth_table_id' => $tableId,
                    'event_date' => $date,
                    'status' => 'open',
                    'is_enabled' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        foreach (array_chunk($slotRows, 200) as $chunk) {
            CothSlot::insert($chunk);
        }

        $this->seedCategoryAndEvent($settings);
    }

    /**
     * Gray and teal are the only chair colours. Odd numbers are gray.
     */
    public function presetSlug($style, $n)
    {
        $gray = ((int) $n) % 2 === 1;
        if ($style === 'couch') {
            return $gray ? 'couch-gray' : 'couch-teal';
        }
        if ($style === 'high_table') {
            return $gray ? 'high-gray' : 'high-teal';
        }

        return $gray ? 'chair-gray' : 'chair-teal';
    }

    protected function seedCategoryAndEvent(CothSetting $settings = null)
    {
        if (Schema::hasTable('categories') && ! \Illuminate\Support\Facades\DB::table('categories')->where('name', 'Chill on the Hill')->whereNull('parent_id')->exists()) {
            $now = date('Y-m-d H:i:s');
            $row = [
                'name' => 'Chill on the Hill',
                'parent_id' => null,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (Schema::hasColumn('categories', 'cloud_tenant_id')) {
                $row['cloud_tenant_id'] = \Illuminate\Support\Facades\DB::table('categories')
                    ->whereNotNull('cloud_tenant_id')
                    ->orderBy('id')
                    ->value('cloud_tenant_id');
            }
            \Illuminate\Support\Facades\DB::table('categories')->insert($row);
        }

        if (! Schema::hasTable('btw_events') || ! Schema::hasTable('event_publications')) {
            return;
        }

        $start = Carbon::parse('2026-12-24 13:00:00', 'Africa/Douala')->timezone(config('app.timezone'));
        $end = Carbon::parse('2027-01-02 23:59:00', 'Africa/Douala')->timezone(config('app.timezone'));
        try {
        $event = Event::where('slug', 'chill-on-the-hill')->first();
        if (! $event) {
            $event = Event::create([
                'name' => 'Chill on the Hill',
                'slug' => 'chill-on-the-hill',
                'event_type' => 'festival',
                'venue' => 'Skyline Hotel',
                'venue_address' => 'Skyline Hotel, Bamenda, Cameroon',
                'city' => 'Bamenda',
                'timezone' => 'Africa/Douala',
                'internal_description' => 'Table reservations for Chill on the Hill.',
                'event_start_at' => $start,
                'event_end_at' => $end,
                'internal_status' => 'ready_for_event',
                'flyer_path' => 'branding/skyline-logo.png',
            ]);
        }

        $pub = EventPublication::where('event_id', $event->id)->first();
        if (! $pub) {
            EventPublication::create([
                'event_id' => $event->id,
                'publish_on_website' => true,
                'public_title' => 'Chill on the Hill',
                'public_summary' => 'Reserve a table at Skyline Hotel, Bamenda, from 24 December 2026 to 2 January 2027.',
                'public_description' => 'Each table is reserved for one selected night. A 20% discount applies on 26–30 December. A non-refundable deposit of 20% confirms the table.',
                'public_flyer_path' => 'branding/skyline-logo.png',
                'public_venue' => 'Skyline Hotel',
                'public_location' => 'Bamenda, Cameroon',
                'registration_url' => url('/chill-on-the-hill'),
                'visibility_at' => now(),
                'is_featured' => true,
                'show_event_time' => true,
                'show_countdown' => true,
                'countdown_target_type' => 'event_start',
                'countdown_completion_message' => 'Event in progress.',
                'hide_countdown_after_completion' => false,
                'publication_status' => 'published',
            ]);
        }

        if ($settings && ! $settings->event_id) {
            $settings->event_id = $event->id;
            $settings->save();
        }
        } catch (\Throwable $e) {
            \Log::warning('coth event: '.$e->getMessage());
        }
    }
}
