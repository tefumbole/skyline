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
        foreach ($query->orderBy('code')->limit(12)->get() as $table) {
            $rows[] = $this->tableCard($table, $date, $settings, $pricing, $extra, $pool);
        }

        return [
            'success' => true,
            'event_date' => $date,
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
        $raw = trim((string) $value);
        if ($raw === '') {
            return [
                'success' => false,
                'ok' => false,
                'error' => 'date_required',
                'message' => 'Which event night? Chill on the Hill books one date at a time.',
                'event_dates' => app(CothCatalog::class)->eventDates(),
            ];
        }
        try {
            $date = Carbon::parse($raw, 'Africa/Douala')->toDateString();
        } catch (\Throwable $e) {
            return ['success' => false, 'ok' => false, 'error' => 'invalid_date', 'message' => 'That date could not be read.'];
        }
        $allowed = app(CothCatalog::class)->eventDates();
        if (! in_array($date, $allowed, true)) {
            return [
                'success' => false,
                'ok' => false,
                'error' => 'outside_event',
                'message' => 'That date is not an open Chill on the Hill night.',
                'event_dates' => $allowed,
            ];
        }

        return ['ok' => true, 'date' => $date];
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
        $available = $table->is_active && $slot && $slot->is_enabled && $slot->status === 'open';

        return [
            'code' => $table->code,
            'section' => $table->section ? $table->section->name : null,
            'section_code' => $table->section ? $table->section->code : null,
            'capacity' => (int) $table->capacity,
            'seating_style' => $table->seating_style,
            'available' => (bool) $available,
            'state' => ! $slot || ! $slot->is_enabled ? 'Closed' : ($slot->status === 'open' ? 'Available' : ($slot->status === 'held' ? 'Held' : 'Booked')),
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
