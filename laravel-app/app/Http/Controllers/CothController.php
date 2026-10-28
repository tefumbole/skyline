<?php

namespace App\Http\Controllers;

use App\CothReservation;
use App\CothSection;
use App\CothSlot;
use App\CothTable;
use App\Event;
use App\Services\BeyondAuthService;
use App\Services\BeyondWasenderService;
use App\Services\Coth\CothCatalog;
use App\Services\Coth\CothPaymentService;
use App\Services\Coth\CothPricing;
use App\Services\Coth\CothReservationService;
use App\Support\SaleInvoiceQr;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CothController extends Controller
{
    public function index(CothCatalog $catalog, CothReservationService $reservations)
    {
        $reservations->releaseExpired();
        $settings = $reservations->settings();
        $event = $settings->event_id ? Event::find($settings->event_id) : Event::where('slug', 'chill-on-the-hill')->first();

        return view('beyond.coth.index', [
            'dates' => $catalog->eventDates(),
            'phase' => $this->phase($event, $settings),
            'event' => $event,
        ]);
    }

    public function tables(Request $request, CothCatalog $catalog, CothReservationService $reservations, CothPricing $pricing)
    {
        $reservations->releaseExpired();
        $date = $request->query('date');
        if (! in_array($date, $catalog->eventDates(), true)) {
            return redirect()->route('coth.index');
        }
        $settings = $reservations->settings();
        $section = $request->query('section') ? CothSection::where('code', $request->query('section'))->where('is_active', 1)->first() : null;
        $sections = CothSection::where('is_active', 1)->orderBy('sort')->get();
        $cards = [];
        foreach ($sections as $row) {
            $open = CothSlot::where('event_date', $date)->where('status', 'open')->where('is_enabled', 1)
                ->whereIn('coth_table_id', CothTable::where('coth_section_id', $row->id)->where('is_active', 1)->pluck('id'))
                ->count();
            $cards[] = ['section' => $row, 'open' => $open];
        }
        $tables = collect();
        $illustrative = true;
        if ($section) {
            $tables = CothTable::where('coth_section_id', $section->id)->orderBy('code')->get()->map(function (CothTable $table) use ($date, $pricing, $settings) {
                $slot = CothSlot::where('coth_table_id', $table->id)->whereDate('event_date', $date)->first();
                $quote = $pricing->quote($table->price, $date, $settings);
                $available = $table->is_active && $slot && $slot->is_enabled && $slot->status === 'open';

                return [
                    'table' => $table,
                    'slot' => $slot,
                    'quote' => $quote,
                    'available' => $available,
                    'state' => ! $slot || ! $slot->is_enabled || ! $table->is_active ? 'Closed' : ($slot->status === 'open' ? 'Available' : ($slot->status === 'held' ? 'Held' : 'Booked')),
                ];
            });
            $illustrative = $tables->contains(function ($row) {
                return $row['table']->pos_x === null || $row['table']->pos_y === null;
            });
        }

        return view('beyond.coth.tables', [
            'date' => $date,
            'section' => $section,
            'cards' => $cards,
            'tables' => $tables,
            'illustrative' => $illustrative,
            'viewMode' => $request->query('view') === 'list' ? 'list' : 'layout',
        ]);
    }

    public function book(Request $request, CothCatalog $catalog, CothReservationService $reservations, CothPricing $pricing)
    {
        $date = $request->query('date');
        $table = CothTable::where('code', $request->query('table'))->where('is_active', 1)->first();
        if (! $table || ! in_array($date, $catalog->eventDates(), true)) {
            abort(404);
        }
        $slot = CothSlot::where('coth_table_id', $table->id)->whereDate('event_date', $date)->first();
        if (! $slot || ! $slot->is_enabled || $slot->status !== 'open') {
            return redirect()->route('coth.tables', ['date' => $date, 'section' => $table->section->code])
                ->with('not_permitted', 'That table is no longer available for this date.');
        }

        $settings = $reservations->settings();
        $extraRate = $pricing->extraChairRate($table);
        $allowChairs = (int) $table->allow_extra_chairs === 1 && $extraRate !== null && (int) $table->max_extra_chairs > 0;

        return view('beyond.coth.book', [
            'date' => $date,
            'table' => $table,
            'quote' => $pricing->quote($table->price, $date, $settings, $table, 0),
            'settings' => $settings,
            'extraRate' => $extraRate,
            'allowChairs' => $allowChairs,
            'maxChairs' => (int) $table->max_extra_chairs,
            'discountIncludesAddons' => (int) ($settings->discount_includes_addons ?? 0) === 1,
        ]);
    }

    /**
     * Resolve a WhatsApp number. Any country code is accepted. Campay's
     * holder_info endpoint only knows CEMAC numbers (237, 236, 240, 241,
     * 242, 243) so a lookup for a different country simply returns an
     * empty name and the person types it manually.
     *
     * Accepts either a prebuilt E.164 string in `phone`, or a `dial_code`
     * + `local` pair. Returns the digits-only normalized number.
     */
    public function resolvePhone(Request $request)
    {
        $data = $request->validate([
            'phone' => 'nullable|string|max:32',
            'dial_code' => 'nullable|string|max:8',
            'local' => 'nullable|string|max:32',
        ]);
        $raw = (string) ($data['phone'] ?? '');
        if ($raw === '' && ! empty($data['dial_code']) && ! empty($data['local'])) {
            $raw = $data['dial_code'].$data['local'];
        }
        $digits = preg_replace('/\D/', '', (string) $raw);
        if (strlen($digits) < 10) {
            return response()->json(['message' => 'Enter a valid WhatsApp number with country code.'], 422);
        }
        // If no explicit dial code and the input looks like a Cameroon local
        // number (8-10 digits that don't start with 237), fall back to the
        // historical sanitizer so legacy clients keep working.
        if (empty($data['dial_code']) && strlen($digits) <= 10 && substr($digits, 0, 3) !== '237') {
            try {
                $digits = \App\Support\WhatsAppPhone::sanitizeForStorage($raw);
            } catch (\Throwable $e) {
                // keep digits as-is
            }
        }
        if ($digits === '' || strlen($digits) < 10) {
            return response()->json(['message' => 'Enter a valid WhatsApp number with country code.'], 422);
        }
        $display = '+'.$digits;
        $name = '';
        $source = '';
        try {
            $resolved = app(\App\Services\Coth\CothHolderLookup::class)->name($digits);
            if ($resolved !== '') {
                $name = $resolved;
                $source = 'campay';
            }
        } catch (\Throwable $e) {
            \Log::warning('coth resolve: '.$e->getMessage());
        }

        return response()->json([
            'phone' => $digits,
            'display_phone' => $display,
            'name' => $name,
            'source' => $source,
        ]);
    }

    public function hold(Request $request, CothReservationService $reservations)
    {
        $data = $request->validate([
            'table_id' => 'required|integer',
            'event_date' => 'required|date',
            'name' => 'required|string|max:191',
            'phone' => 'required|string|max:32',
            'email' => 'nullable|email|max:191',
            'pay_choice' => 'required|in:deposit,full',
            'extra_chairs' => 'nullable|integer|min:0|max:50',
            'terms' => 'accepted',
        ]);
        $table = CothTable::where('id', $data['table_id'])->where('is_active', 1)->first();
        if (! $table) {
            return back()->with('not_permitted', 'Choose an available table.');
        }
        try {
            $reservation = $reservations->hold($table, $data['event_date'], $data);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('not_permitted', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->withInput()->with('not_permitted', 'Enter a valid WhatsApp number.');
        }
        $this->dispatchReservationOtp($reservation);

        return redirect()->route('coth.verify', $reservation->public_token);
    }

    /**
     * Back-compat router: send old /checkout links to the correct stage.
     */
    public function checkout($token, CothReservationService $reservations)
    {
        $reservations->releaseExpired();
        $reservation = CothReservation::where('public_token', $token)->firstOrFail();
        if ($reservation->status === 'confirmed') {
            return redirect()->route('coth.show', $token);
        }
        if ($reservation->status !== 'held') {
            return redirect()->route('coth.index')->with('not_permitted', 'This hold is '.$reservation->status.'. Choose another table.');
        }
        if (! $reservation->phone_verified_at) {
            return redirect()->route('coth.verify', $token);
        }

        return redirect()->route('coth.payment', $token);
    }

    public function verify($token, CothReservationService $reservations)
    {
        $reservations->releaseExpired();
        $reservation = CothReservation::where('public_token', $token)->firstOrFail();
        $reservation->load('table.section');
        if ($reservation->status === 'confirmed') {
            return redirect()->route('coth.show', $token);
        }
        if ($reservation->status !== 'held') {
            return redirect()->route('coth.index')->with('not_permitted', 'This hold is '.$reservation->status.'. Choose another table.');
        }
        if ($reservation->hold_expires_at && $reservation->hold_expires_at->isPast()) {
            return redirect()->route('coth.index')->with('not_permitted', 'This hold has expired. Choose the table again.');
        }
        if ($reservation->phone_verified_at) {
            return redirect()->route('coth.payment', $token);
        }

        return view('beyond.coth.verify', ['reservation' => $reservation]);
    }

    public function payment($token, CothReservationService $reservations, CothPaymentService $payments)
    {
        $reservations->releaseExpired();
        $reservation = CothReservation::where('public_token', $token)->firstOrFail();
        $reservation->load('table.section');
        if ($reservation->status === 'confirmed') {
            return redirect()->route('coth.show', $token);
        }
        if ($reservation->status !== 'held') {
            return redirect()->route('coth.index')->with('not_permitted', 'This hold is '.$reservation->status.'. Choose another table.');
        }
        if ($reservation->hold_expires_at && $reservation->hold_expires_at->isPast()) {
            return redirect()->route('coth.index')->with('not_permitted', 'This hold has expired. Choose the table again.');
        }
        if (! $reservation->phone_verified_at) {
            return redirect()->route('coth.verify', $token);
        }

        return view('beyond.coth.payment', [
            'reservation' => $reservation,
            'momo' => $payments->momoConfigured(),
            'card' => $payments->cardConfigured(),
        ]);
    }

    public function verifyOtp(Request $request, $token, BeyondAuthService $auth)
    {
        $reservation = CothReservation::where('public_token', $token)->where('status', 'held')->firstOrFail();
        $request->validate(['otp' => 'required|digits:6']);
        $result = $auth->verifyOtp($reservation->phone, $request->input('otp'), 'coth_reservation');
        if (empty($result['success'])) {
            return back()->with('not_permitted', isset($result['error']) ? $result['error'] : 'That code is not valid.');
        }
        $reservation->phone_verified_at = now();
        $reservation->save();

        return redirect()->route('coth.payment', $token);
    }

    public function resendOtp($token)
    {
        $reservation = CothReservation::where('public_token', $token)->where('status', 'held')->firstOrFail();
        $this->dispatchReservationOtp($reservation);

        return back()->with('message', 'A new code was sent.');
    }

    public function pay(Request $request, $token, CothPaymentService $payments)
    {
        $reservation = CothReservation::where('public_token', $token)->where('status', 'held')->firstOrFail();
        if (! $reservation->phone_verified_at) {
            return back()->with('not_permitted', 'Verify the phone number first.');
        }
        if ($reservation->hold_expires_at && $reservation->hold_expires_at->isPast()) {
            return back()->with('not_permitted', 'This hold has expired. Choose the table again.');
        }
        $method = $request->input('method') === 'card' ? 'card' : 'momo';
        try {
            $link = $payments->start($reservation, $method);
        } catch (\Throwable $e) {
            return back()->with('not_permitted', $e->getMessage());
        }
        $reservation->payment_status = 'pending';
        $reservation->pay_method = $method;
        $reservation->save();

        return redirect()->away($link);
    }

    public function campayReturn(Request $request, CothPaymentService $payments, CothReservationService $reservations)
    {
        $reservation = $payments->handleCampay($request, $reservations);
        if (! $reservation) {
            return redirect()->route('coth.index')->with('not_permitted', 'Payment could not be matched to a reservation.');
        }

        return redirect()->route('coth.show', $reservation->public_token);
    }

    public function stripeReturn(Request $request, CothPaymentService $payments, CothReservationService $reservations)
    {
        $reservation = $payments->handleStripe($request->query('session_id'), $reservations);
        if (! $reservation) {
            return redirect()->route('coth.index')->with('not_permitted', 'Card payment could not be confirmed.');
        }

        return redirect()->route('coth.show', $reservation->public_token);
    }

    public function show($token)
    {
        $reservation = CothReservation::where('public_token', $token)->firstOrFail();
        $reservation->load('table.section', 'sale');
        $qr = null;
        if ($reservation->sale) {
            try {
                $qr = SaleInvoiceQr::dataUri($reservation->sale, false);
            } catch (\Throwable $e) {
                $qr = null;
            }
        }

        return view('beyond.coth.show', [
            'reservation' => $reservation,
            'qr' => $qr,
            'verifyUrl' => $reservation->sale ? SaleInvoiceQr::scanUrl($reservation->sale) : null,
        ]);
    }

    protected function dispatchReservationOtp(CothReservation $reservation)
    {
        try {
            $auth = app(BeyondAuthService::class);
            $otp = $auth->createOtp($reservation->phone, 'coth_reservation');
            app(BeyondWasenderService::class)->sendOtp($reservation->phone, $otp['code'], 'reservation');
        } catch (\Throwable $e) {
            \Log::warning('coth otp: '.$e->getMessage());
        }
    }

    protected function phase($event, $settings)
    {
        $tz = $settings->timezone ?: 'Africa/Douala';
        $start = $event && $event->event_start_at
            ? $event->event_start_at->copy()->timezone($tz)
            : Carbon::parse('2026-12-24 13:00:00', $tz);
        $end = $event && $event->event_end_at
            ? $event->event_end_at->copy()->timezone($tz)
            : Carbon::parse('2027-01-02 23:59:00', $tz);
        $now = Carbon::now($tz);
        if ($now->gte($start) && $now->lte($end)) {
            return ['label' => 'Event in progress.', 'countdown' => null];
        }
        if ($now->gt($end)) {
            return ['label' => 'This event has ended.', 'countdown' => null];
        }

        return ['label' => 'Starts 24 December 2026 at 1:00 PM', 'countdown' => $start->toIso8601String()];
    }
}
