<?php

namespace App\Services\Assistant;

use App\Category;
use App\CothExtraChairInventory;
use App\CothSection;
use App\CothSetting;
use App\CothSlot;
use App\CothTable;
use App\Event;
use App\EventPublication;
use App\Product;
use App\Services\Coth\CothCatalog;
use App\Services\Coth\CothPaymentService;
use App\Services\Coth\CothPricing;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only answers for Skyline Hotel and Chill on the Hill.
 * Prices and availability come from the existing COTH services. This class
 * does not create bookings, quotations, orders, or payments.
 */
class SkylineGuestGuide
{
    public function searchProducts(array $params)
    {
        if (! Schema::hasTable('products')) {
            return ['success' => false, 'error' => 'catalogue_unavailable'];
        }
        $term = trim((string) (isset($params['query']) ? $params['query'] : ''));
        $query = Product::query()->where('is_active', true);
        if ($term !== '') {
            $query->where(function ($inner) use ($term) {
                $inner->where('name', 'like', '%'.$term.'%')->orWhere('code', 'like', '%'.$term.'%');
            });
        }
        $rows = $query->orderBy('name')->limit(8)->get(['id', 'name', 'code', 'price', 'qty', 'category_id', 'product_details']);
        $categories = $this->categoryNames($rows->pluck('category_id')->all());
        $products = [];
        foreach ($rows as $row) {
            $products[] = [
                'name' => $row->name,
                'code' => $row->code,
                'price_fcfa' => (int) $row->price,
                'currency' => 'XAF',
                'in_stock' => (float) $row->qty > 0,
                'category' => isset($categories[$row->category_id]) ? $categories[$row->category_id] : null,
                'summary' => $this->plain($row->product_details, 180),
            ];
        }

        return [
            'success' => true,
            'products' => $products,
            'currency' => 'XAF',
            'links' => $this->safeLinks([
                $this->link('Order Food', 'restaurant.menu'),
            ]),
            'note' => 'Prices are the current catalogue price per item. This is not an order.',
        ];
    }

    public function product(array $params)
    {
        $found = $this->searchProducts($params);
        if (empty($found['products'])) {
            return ['success' => false, 'error' => 'not_found', 'message' => 'No published product matched that name or code.'];
        }
        $found['product'] = $found['products'][0];

        return $found;
    }

    public function event()
    {
        $settings = $this->settings();
        $stamp = $settings && $settings->updated_at ? $settings->updated_at->timestamp : 0;
        $payload = Cache::remember('skyline-guest-event-'.$stamp, 600, function () use ($settings) {
            return $this->eventPayload($settings);
        });

        return $payload;
    }

    public function tables(array $params)
    {
        $parsed = $this->eventDate(isset($params['event_date']) ? $params['event_date'] : (isset($params['date']) ? $params['date'] : ''));
        if (empty($parsed['ok'])) {
            if (isset($parsed['error']) && $parsed['error'] === 'date_required') {
                return $this->publishedMenu();
            }

            return $parsed;
        }
        $date = $parsed['date'];
        if (! Schema::hasTable('coth_tables')) {
            return ['success' => false, 'error' => 'tables_unavailable'];
        }
        $settings = $this->settings();
        $pricing = app(CothPricing::class);
        $capacity = isset($params['capacity']) ? (int) $params['capacity'] : 0;
        $extra = isset($params['extra_chairs']) ? (int) $params['extra_chairs'] : 0;
        $sectionCode = strtolower(trim((string) (isset($params['section']) ? $params['section'] : '')));
        $query = CothTable::query()->where('is_active', 1)->with('section');
        if ($capacity > 0) {
            $query->where('capacity', $capacity);
        }
        if ($sectionCode !== '') {
            $query->whereHas('section', function ($inner) use ($sectionCode) {
                $inner->where('code', $sectionCode)->orWhere('name', 'like', '%'.$sectionCode.'%');
            });
        }
        $pool = $this->chairPool($date);
        $rows = [];
        foreach ($query->orderBy('code')->get() as $table) {
            $rows[] = $this->tableCard($table, $date, $settings, $pricing, $extra, $pool);
        }
        $open = 0;
        foreach ($rows as $row) {
            if (! empty($row['available'])) {
                $open++;
            }
        }

        return [
            'success' => true,
            'event_date' => $date,
            'open_count' => $open,
            'table_count' => count($rows),
            'message' => $open > 0
                ? $open.' tables are open on '.Carbon::parse($date)->format('j F Y').'.'
                : 'No table is still open on '.Carbon::parse($date)->format('j F Y').'. Another Chill on the Hill night may still have space.',
            'instruction' => $open > 0
                ? 'Tell the guest the open tables, sections, and prices. Do not say that the tables are unavailable.'
                : 'This night is full. Offer another open Chill on the Hill night. Do not say the event has no tables.',
            'currency' => 'XAF',
            'timezone' => $settings ? ($settings->timezone ?: 'Africa/Douala') : 'Africa/Douala',
            'tables' => $rows,
            'coth_section' => $sectionCode !== '' ? $sectionCode : null,
            'links' => $this->safeLinks([
                $this->link('View Tables', 'coth.tables', array_filter([
                    'date' => $date,
                    'section' => $sectionCode !== '' ? $sectionCode : null,
                ])),
            ]),
            'note' => 'A chat answer is not a reservation. Availability is checked again on the booking page.',
        ];
    }

    public function quote(array $params)
    {
        $parsed = $this->eventDate(isset($params['event_date']) ? $params['event_date'] : (isset($params['date']) ? $params['date'] : ''));
        if (empty($parsed['ok'])) {
            return $parsed;
        }
        $code = trim((string) (isset($params['table']) ? $params['table'] : (isset($params['table_code']) ? $params['table_code'] : '')));
        if ($code === '') {
            return $this->tables($params);
        }
        if (! Schema::hasTable('coth_tables')) {
            return ['success' => false, 'error' => 'tables_unavailable'];
        }
        $table = CothTable::where('code', $code)->where('is_active', 1)->with('section')->first();
        if (! $table) {
            return ['success' => false, 'error' => 'not_found', 'message' => 'No published table uses that code.'];
        }
        $settings = $this->settings();
        $extra = isset($params['extra_chairs']) ? (int) $params['extra_chairs'] : 0;
        $card = $this->tableCard($table, $parsed['date'], $settings, app(CothPricing::class), $extra, $this->chairPool($parsed['date']));
        $section = $table->section ? $table->section->code : null;

        return [
            'success' => true,
            'event_date' => $parsed['date'],
            'coth_table_code' => $table->code,
            'coth_section' => $section,
            'currency' => 'XAF',
            'table' => $card,
            'links' => $this->safeLinks(array_filter([
                ! empty($card['available']) ? $this->link('Book This Table', 'coth.book', ['date' => $parsed['date'], 'table' => $table->code]) : null,
                $this->link('View Tables', 'coth.tables', array_filter(['date' => $parsed['date'], 'section' => $section])),
            ])),
            'note' => 'This price is not a quotation and does not hold the table.',
        ];
    }

    public function payments()
    {
        $pay = app(CothPaymentService::class);

        return [
            'success' => true,
            'currency' => 'XAF',
            'methods' => [
                ['code' => 'momo', 'label' => 'Momo', 'enabled' => $pay->momoConfigured(), 'covers' => 'MTN and Orange through Campay', 'where' => 'Chill on the Hill checkout after a table is held on the website'],
                ['code' => 'card', 'label' => 'Card', 'enabled' => $pay->cardConfigured(), 'covers' => 'Card checkout through the configured card provider', 'where' => 'Chill on the Hill checkout after a table is held on the website'],
            ],
            'food_order' => 'Food is ordered on the public menu. A payment link for a specific order is issued by the restaurant and is not created in chat.',
            'links' => $this->safeLinks([
                $this->link('View Tables', 'coth.index'),
                $this->link('Order Food', 'restaurant.menu'),
            ]),
        ];
    }

    public function links(array $params)
    {
        $kind = strtolower(trim((string) (isset($params['kind']) ? $params['kind'] : 'tables')));
        $date = '';
        if (! empty($params['event_date']) || ! empty($params['date'])) {
            $parsed = $this->eventDate(isset($params['event_date']) ? $params['event_date'] : $params['date']);
            if (! empty($parsed['ok'])) {
                $date = $parsed['date'];
            }
        }
        $section = strtolower(trim((string) (isset($params['section']) ? $params['section'] : '')));
        $table = trim((string) (isset($params['table']) ? $params['table'] : ''));
        $items = [];
        if ($kind === 'menu' || $kind === 'food' || $kind === 'order') {
            $items[] = $this->link('Order Food', 'restaurant.menu');
        } elseif ($kind === 'book' && $date !== '' && $table !== '') {
            $items[] = $this->link('Book This Table', 'coth.book', ['date' => $date, 'table' => $table]);
        } else {
            $items[] = $this->link('View Tables', $date !== '' ? 'coth.tables' : 'coth.index', $date !== '' ? array_filter(['date' => $date, 'section' => $section !== '' ? $section : null]) : []);
        }

        return ['success' => true, 'links' => $this->safeLinks($items)];
    }

    public function eventDate($value)
    {
        $allowed = app(CothCatalog::class)->eventDates();
        $raw = trim((string) $value);
        if ($raw === '') {
            return [
                'success' => false,
                'ok' => false,
                'error' => 'date_required',
                'message' => 'Which event night? Chill on the Hill books one date at a time.',
                'event_dates' => $allowed,
                'event_dates_spoken' => $this->spokenDates($allowed),
            ];
        }
        $date = $this->readEventDate($raw, $allowed);
        if ($date === null) {
            return [
                'success' => false,
                'ok' => false,
                'error' => 'invalid_date',
                'message' => 'That date could not be read. Chill on the Hill nights are '.$this->spokenDates($allowed).'.',
                'event_dates' => $allowed,
            ];
        }
        if (! in_array($date, $allowed, true)) {
            return [
                'success' => false,
                'ok' => false,
                'error' => 'outside_event',
                'message' => 'That date is not an open Chill on the Hill night.',
                'event_dates' => $allowed,
                'event_dates_spoken' => $this->spokenDates($allowed),
            ];
        }

        return ['ok' => true, 'date' => $date];
    }

    public function promptFacts()
    {
        $dates = $this->spokenDates(app(CothCatalog::class)->eventDates());
        $lines = [];
        $lines[] = 'Chill on the Hill brief. Use it for dates, tables, and prices.';
        $lines[] = 'Open nights: '.$dates.'. Closed inside that span: 28 December 2026 and 29 December 2026. One booking is one night.';
        $lines[] = 'Deposit is 20 percent of the night price and is not refundable. Apply a discount only when the quote tool returns one.';
        foreach ($this->resolvedSections() as $section) {
            $bits = [];
            foreach ($section['options'] as $option) {
                $bits[] = $option['capacity'].'-seat '.$option['seating'].' '.number_format($option['list_price_fcfa']).' FCFA ('.$option['tables'].' tables)';
            }
            $lines[] = $section['name'].': '.implode('; ', $bits).'.';
        }
        $lines[] = 'These tables are published and can be booked on an open night. If the guest asks which tables are available and has not named a night, list these options and the open nights. Do not say the tables are unavailable. A table is unavailable only when that night is Held or Booked.';
        $lines[] = 'Read a date however the guest writes it: 24th December, 24/12, Dec 24, Christmas Eve, or the 26th.';

        return implode("\n", $lines);
    }

    public function publishedMenu()
    {
        $dates = app(CothCatalog::class)->eventDates();
        $sections = $this->resolvedSections();

        return [
            'success' => true,
            'needs_night' => true,
            'message' => 'These Chill on the Hill tables can be booked. Open nights: '.$this->spokenDates($dates).'.',
            'instruction' => 'The guest has not chosen a night. List these published tables, their prices, and the open nights. Do not say the tables are unavailable.',
            'event_dates' => $dates,
            'event_dates_spoken' => $this->spokenDates($dates),
            'closed_dates_inside_span' => ['2026-12-28', '2026-12-29'],
            'currency' => 'XAF',
            'deposit_percent' => 20,
            'sections' => $sections,
            'links' => $this->safeLinks([
                $this->link('View Tables', 'coth.index'),
            ]),
        ];
    }

    public function safeLinks(array $links)
    {
        $allowed = [
            'coth.index' => '/chill-on-the-hill',
            'coth.tables' => '/chill-on-the-hill/tables',
            'coth.book' => '/chill-on-the-hill/book',
            'restaurant.menu' => '/menu',
        ];
        $out = [];
        foreach ($links as $link) {
            if (! is_array($link) || empty($link['route']) || empty($link['label'])) {
                continue;
            }
            if (! isset($allowed[$link['route']])) {
                continue;
            }
            $path = $allowed[$link['route']];
            $query = isset($link['query']) && is_array($link['query']) ? $link['query'] : [];
            $clean = [];
            foreach (['date', 'section', 'table', 'view'] as $key) {
                if (! isset($query[$key]) || $query[$key] === '' || $query[$key] === null) {
                    continue;
                }
                $clean[$key] = (string) $query[$key];
            }
            $url = url($path).($clean ? '?'.http_build_query($clean) : '');
            if (strpos($url, '/checkout') !== false || strpos($url, '/payment/') !== false || strpos($url, '/admin') !== false) {
                continue;
            }
            $out[] = ['label' => (string) $link['label'], 'url' => $url];
        }

        return $out;
    }

    protected function eventPayload(CothSetting $settings = null)
    {
        $catalog = app(CothCatalog::class);
        $dates = $catalog->eventDates();
        $discountDates = $settings ? $settings->discountDateList() : [];
        $publication = null;
        $eventStart = null;
        if (Schema::hasTable('btw_events')) {
            $event = Event::where('slug', 'chill-on-the-hill')->first();
            if ($event && $event->event_start_at) {
                $tz = $settings && $settings->timezone ? $settings->timezone : 'Africa/Douala';
                $eventStart = $event->event_start_at->copy()->timezone($tz)->format('Y-m-d H:i');
            }
            if ($event && Schema::hasTable('event_publications')) {
                $publication = EventPublication::where('event_id', $event->id)->where('publication_status', 'published')->first();
            }
        }
        $conflicts = [];
        $text = $publication ? trim((string) $publication->public_description.' '.$publication->public_summary) : '';
        if ($text !== '' && stripos($text, '26') !== false && stripos($text, 'discount') !== false && count($discountDates) === 0) {
            $conflicts[] = 'The published event text mentions a discount, but the live discount date list is empty. Quote from the live discount dates, not from the description.';
        }
        if ($eventStart && substr($eventStart, 11, 5) === '13:00') {
            $conflicts[] = 'The stored event start is 13:00 Africa/Douala. That is the countdown time. Nightly operating hours are not stored as a separate setting, so do not call 13:00 the nightly opening.';
        }

        return [
            'success' => true,
            'name' => 'Chill on the Hill',
            'venue' => 'Skyline Hotel, Bamenda',
            'timezone' => $settings && $settings->timezone ? $settings->timezone : 'Africa/Douala',
            'currency' => 'XAF',
            'event_dates' => $dates,
            'closed_dates_inside_span' => ['2026-12-28', '2026-12-29'],
            'reservation_span' => 'One selected event date. The booking does not create a second night on the next calendar date.',
            'configured_event_start' => $eventStart,
            'configured_event_start_role' => 'countdown_and_published_event_start',
            'nightly_operating_hours' => null,
            'nightly_operating_hours_status' => 'not_configured',
            'discount_percent' => $settings ? (int) $settings->discount_percent : 0,
            'discount_dates' => $discountDates,
            'sections' => $this->resolvedSections(),
            'message' => 'Chill on the Hill tables are published. Ask which night the guest wants if they have not chosen one.',
            'instruction' => 'If the guest asks which tables are available and has not named a night, list the sections, seat counts, and prices. Do not say the tables are unavailable.',
            'deposit_percent' => $settings ? (int) $settings->deposit_percent : 0,
            'deposit_refundable' => false,
            'published_summary' => $publication ? $this->plain($publication->public_summary, 400) : null,
            'conflicts' => $conflicts,
            'links' => $this->safeLinks([$this->link('View Tables', 'coth.index')]),
        ];
    }

    protected function tableCard(CothTable $table, $date, CothSetting $settings = null, CothPricing $pricing, $extra, $pool)
    {
        $limit = $pricing->extraChairLimit($table);
        $requested = max(0, (int) $extra);
        $allowed = $requested;
        $chairNote = null;
        if ($requested > 0) {
            $cap = $limit;
            if ($pool !== null) {
                $cap = min($cap, $pool);
            }
            if ($requested > $cap) {
                $allowed = $cap;
                $chairNote = 'Requested '.$requested.' extra chairs. This table can add '.$cap.' on that night.';
            }
        }
        $quote = $settings
            ? $pricing->quote($table->price, $date, $settings, $table, $allowed)
            : null;
        $slot = Schema::hasTable('coth_slots')
            ? CothSlot::where('coth_table_id', $table->id)->whereDate('event_date', $date)->first()
            : null;
        if (! $table->is_active) {
            $available = false;
            $state = 'Closed';
        } elseif (! $slot) {
            $available = true;
            $state = 'Published';
        } elseif (! $slot->is_enabled) {
            $available = false;
            $state = 'Closed';
        } elseif ($slot->status === 'open') {
            $available = true;
            $state = 'Available';
        } elseif ($slot->status === 'held') {
            $available = false;
            $state = 'Held';
        } else {
            $available = false;
            $state = 'Booked';
        }

        return [
            'code' => $table->code,
            'section' => $table->section ? $this->guestSectionName($table->section->code, $table->section->name) : null,
            'section_code' => $table->section ? $table->section->code : null,
            'capacity' => (int) $table->capacity,
            'seating_style' => $table->seating_style,
            'available' => (bool) $available,
            'state' => $state,
            'list_price_fcfa' => (int) $table->price,
            'quote' => $quote,
            'balance_after_deposit_fcfa' => $quote ? ((int) $quote['booking_total'] - (int) $quote['deposit_due']) : null,
            'extra_chair_limit' => $limit,
            'extra_chairs_applied' => $allowed,
            'extra_chair_note' => $chairNote,
            'currency' => 'XAF',
            'per' => 'table for the selected event night',
        ];
    }

    protected function chairPool($date)
    {
        if (! Schema::hasTable('coth_extra_chair_inventory')) {
            return null;
        }
        $row = CothExtraChairInventory::whereDate('event_date', $date)->first();

        return $row ? $row->available() : null;
    }

    protected function settings()
    {
        if (! Schema::hasTable('coth_settings')) {
            return null;
        }

        return CothSetting::query()->first();
    }

    protected function categoryNames(array $ids)
    {
        $ids = array_values(array_filter(array_unique($ids)));
        if (! $ids || ! Schema::hasTable('categories')) {
            return [];
        }

        return Category::whereIn('id', $ids)->pluck('name', 'id')->all();
    }

    protected function resolvedSections()
    {
        if (! Schema::hasTable('coth_tables') || ! Schema::hasTable('coth_sections')) {
            return $this->publishedSections();
        }
        try {
            $tables = CothTable::query()->where('is_active', 1)->with('section')->orderBy('code')->get();
        } catch (\Throwable $e) {
            return $this->publishedSections();
        }
        if ($tables->isEmpty()) {
            return $this->publishedSections();
        }
        $grouped = [];
        foreach ($tables as $table) {
            $code = $table->section ? $table->section->code : 'table';
            if (! isset($grouped[$code])) {
                $grouped[$code] = [
                    'code' => $code,
                    'name' => $this->guestSectionName($code, $table->section ? $table->section->name : 'Table'),
                    'options' => [],
                ];
            }
            $key = (int) $table->capacity.'|'.$table->seating_style.'|'.(int) $table->price;
            if (! isset($grouped[$code]['options'][$key])) {
                $grouped[$code]['options'][$key] = [
                    'capacity' => (int) $table->capacity,
                    'seating' => str_replace('_', ' ', (string) $table->seating_style),
                    'tables' => 0,
                    'list_price_fcfa' => (int) $table->price,
                    'currency' => 'XAF',
                    'per' => 'table for one event night',
                ];
            }
            $grouped[$code]['options'][$key]['tables']++;
        }
        $out = [];
        foreach ($grouped as $section) {
            $section['options'] = array_values($section['options']);
            $out[] = $section;
        }

        return $out;
    }

    protected function publishedSections()
    {
        $out = [];
        foreach (app(CothCatalog::class)->sections() as $section) {
            $options = [];
            foreach ($section['rows'] as $row) {
                $options[] = [
                    'capacity' => (int) $row['capacity'],
                    'seating' => str_replace('_', ' ', $row['style']),
                    'tables' => (int) $row['count'],
                    'list_price_fcfa' => (int) $row['price'],
                    'currency' => 'XAF',
                    'per' => 'table for one event night',
                ];
            }
            $out[] = [
                'code' => $section['code'],
                'name' => $this->guestSectionName($section['code'], $section['name']),
                'options' => $options,
            ];
        }

        return $out;
    }

    protected function guestSectionName($code, $fallback)
    {
        if ($code === 'yard') {
            return 'Premium — GUARDEN';
        }

        return (string) $fallback;
    }

    protected function spokenDates(array $dates)
    {
        $spoken = [];
        foreach ($dates as $date) {
            $spoken[] = Carbon::parse($date)->format('j F Y');
        }

        return implode(', ', $spoken);
    }

    protected function readEventDate($raw, array $allowed)
    {
        $text = strtolower(trim($raw));
        $text = str_replace(['’', '`'], "'", $text);
        $text = preg_replace('/(\d+)\s*(st|nd|rd|th)\b/i', '$1', $text);
        $named = [
            'christmas eve' => '2026-12-24',
            'christmas day' => '2026-12-25',
            'christmas' => '2026-12-25',
            'boxing day' => '2026-12-26',
            'new year\'s eve' => '2026-12-31',
            'new years eve' => '2026-12-31',
            'new year eve' => '2026-12-31',
            'new year\'s day' => '2027-01-01',
            'new years day' => '2027-01-01',
            'new year' => '2027-01-01',
            'nouvel an' => '2027-01-01',
            'noël' => '2026-12-25',
            'noel' => '2026-12-25',
            'réveillon' => '2026-12-31',
            'reveillon' => '2026-12-31',
        ];
        foreach ($named as $phrase => $date) {
            if (strpos($text, $phrase) !== false) {
                return $date;
            }
        }
        $months = [
            'janvier' => 'january', 'février' => 'february', 'fevrier' => 'february', 'mars' => 'march',
            'avril' => 'april', 'mai' => 'may', 'juin' => 'june', 'juillet' => 'july',
            'août' => 'august', 'aout' => 'august', 'septembre' => 'september', 'octobre' => 'october',
            'novembre' => 'november', 'décembre' => 'december', 'decembre' => 'december',
        ];
        $text = strtr($text, $months);
        $short = [
            'jan' => 'january', 'feb' => 'february', 'mar' => 'march', 'apr' => 'april',
            'jun' => 'june', 'jul' => 'july', 'aug' => 'august', 'sep' => 'september',
            'sept' => 'september', 'oct' => 'october', 'nov' => 'november', 'dec' => 'december',
        ];
        foreach ($short as $from => $to) {
            $text = preg_replace('/\b'.$from.'\b/', $to, $text);
        }
        if (preg_match('/\b(\d{1,2})[\/\-.](\d{1,2})(?:[\/\-.](\d{2,4}))?\b/', $text, $match)) {
            $day = (int) $match[1];
            $month = (int) $match[2];
            $year = isset($match[3]) && $match[3] !== '' ? (int) $match[3] : 0;
            if ($month > 12 && $day <= 12) {
                $swap = $day;
                $day = $month;
                $month = $swap;
            }
            if ($year > 0 && $year < 100) {
                $year += 2000;
            }
            if ($year === 0) {
                $year = $month === 1 ? 2027 : 2026;
            }
            if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }
        if (! preg_match('/\d{4}/', $text) && ! preg_match('/january|february|march|april|may|june|july|august|september|october|november|december/', $text)) {
            if (preg_match('/\b(\d{1,2})\b/', $text, $match)) {
                $day = sprintf('%02d', (int) $match[1]);
                foreach ($allowed as $date) {
                    if (substr($date, -2) === $day) {
                        return $date;
                    }
                }
            }
        }
        if (! preg_match('/\d|january|february|march|april|may|june|july|august|september|october|november|december|christmas|noel|boxing|reveillon|nouvel/', $text)) {
            return null;
        }
        try {
            $parsed = Carbon::parse($text, 'Africa/Douala');
            if ((int) $parsed->month === 1 && (int) $parsed->year < 2027) {
                $parsed->year(2027);
            } elseif ((int) $parsed->year < 2026) {
                $parsed->year(2026);
            }

            return $parsed->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function link($label, $route, array $query = [])
    {
        return ['label' => $label, 'route' => $route, 'query' => $query];
    }

    protected function plain($value, $limit)
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $value)));
        if (strlen($text) <= $limit) {
            return $text;
        }

        return substr($text, 0, $limit - 1).'…';
    }
}
