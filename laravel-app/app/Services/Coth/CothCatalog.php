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
                ['capacity' => 4, 'count' => 4, 'price' => 500000, 'couch' => 0],
                ['capacity' => 6, 'count' => 4, 'price' => 600000, 'couch' => 0],
                ['capacity' => 8, 'count' => 2, 'price' => 800000, 'couch' => 0],
                ['capacity' => 10, 'count' => 2, 'price' => 1000000, 'couch' => 0],
            ]],
            ['code' => 'outer', 'name' => 'Premium — Outer Restaurant', 'prefix' => 'OUTER', 'rows' => [
                ['capacity' => 4, 'count' => 5, 'price' => 500000, 'couch' => 0],
                ['capacity' => 6, 'count' => 1, 'price' => 600000, 'couch' => 0],
                ['capacity' => 8, 'count' => 2, 'price' => 800000, 'couch' => 0],
                ['capacity' => 10, 'count' => 1, 'price' => 1000000, 'couch' => 0],
            ]],
            ['code' => 'hall', 'name' => 'Gold — Hall', 'prefix' => 'HALL', 'rows' => [
                ['capacity' => 4, 'count' => 2, 'price' => 300000, 'couch' => 0],
                ['capacity' => 6, 'count' => 8, 'price' => 500000, 'couch' => 2],
                ['capacity' => 10, 'count' => 2, 'price' => 800000, 'couch' => 0],
            ]],
            ['code' => 'pool', 'name' => 'Silver — Pool', 'prefix' => 'POOL', 'rows' => [
                ['capacity' => 2, 'count' => 2, 'price' => 150000, 'couch' => 0],
                ['capacity' => 3, 'count' => 1, 'price' => 250000, 'couch' => 0],
                ['capacity' => 4, 'count' => 5, 'price' => 350000, 'couch' => 1],
                ['capacity' => 6, 'count' => 1, 'price' => 500000, 'couch' => 1],
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
            foreach ($sectionDef['rows'] as $row) {
                $couchFrom = $row['count'] - $row['couch'];
                for ($i = 1; $i <= $row['count']; $i++) {
                    $style = $i > $couchFrom ? 'couch' : 'standard';
                    $code = $sectionDef['prefix'].'-T'.$row['capacity'].'-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                    CothTable::create([
                        'coth_section_id' => $section->id,
                        'code' => $code,
                        'name' => $code,
                        'capacity' => $row['capacity'],
                        'seating_style' => $style,
                        'price' => $row['price'],
                        'is_active' => 1,
                    ]);
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
