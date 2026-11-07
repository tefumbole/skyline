<?php

namespace App\Services\Coth;

use App\Biller;
use App\CothExtraChairInventory;
use App\CothReservation;
use App\CothSetting;
use App\CothSlot;
use App\CothTable;
use App\Customer;
use App\CustomerGroup;
use App\Payment;
use App\Sale;
use App\Support\WhatsAppPhone;
use App\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CothReservationService
{
    public function settings()
    {
        $settings = CothSetting::query()->first();
        if ($settings) {
            return $settings;
        }

        return CothSetting::create([
            'deposit_percent' => 20,
            'discount_percent' => 0,
            'hold_minutes' => 15,
            'discount_dates' => json_encode(app(CothCatalog::class)->discountDates()),
            'timezone' => 'Africa/Douala',
        ]);
    }

    public function releaseExpired()
    {
        $expired = CothReservation::where('status', 'held')
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<', now())
            ->get();
        foreach ($expired as $reservation) {
            DB::transaction(function () use ($reservation) {
                $row = CothReservation::where('id', $reservation->id)->lockForUpdate()->first();
                if (! $row || $row->status !== 'held') {
                    return;
                }
                $row->status = 'expired';
                $row->save();
                $this->setSlotStatus($row, 'held', 'open');
                $this->releaseChairHold($row);
            });
        }

        return $expired->count();
    }

    public function hold(CothTable $table, $eventDate, array $guest)
    {
        $date = Carbon::parse($eventDate)->toDateString();
        $settings = $this->settings();
        if (! in_array($date, app(CothCatalog::class)->eventDates(), true)) {
            throw new \InvalidArgumentException('Choose a date between 24 December 2026 and 2 January 2027.');
        }
        if (empty($guest['terms'])) {
            throw new \InvalidArgumentException('Accept the non-refundable deposit terms to continue.');
        }
        $nights = (int) ($guest['nights'] ?? 1);
        if ($nights < 1) {
            $nights = 1;
        }
        if ($nights > 8) {
            throw new \InvalidArgumentException('A stay can be from 1 to 8 days.');
        }
        $dates = $this->consecutiveDates($date, $nights);
        if (count($dates) !== $nights) {
            throw new \InvalidArgumentException('Those days are not all open. Choose fewer days or another start date.');
        }
        $payChoice = (isset($guest['pay_choice']) && in_array($guest['pay_choice'], ['full', 'custom', 'later'], true)) ? $guest['pay_choice'] : 'deposit';
        $extraChairs = max(0, (int) ($guest['extra_chairs'] ?? 0));

        if ($extraChairs > 0) {
            $limit = app(CothPricing::class)->extraChairLimit($table);
            if ($limit < 1) {
                throw new \InvalidArgumentException('Extra chairs are not available on this table.');
            }
            if ($extraChairs > $limit) {
                throw new \InvalidArgumentException('That exceeds the extra chairs available for this table.');
            }
        }

        $quote = app(CothPricing::class)->quoteStay($table, $dates, $settings, $extraChairs);
        if ($payChoice === 'later') {
            $amountDue = 0;
        } elseif ($payChoice === 'custom') {
            $custom = (int) ($guest['custom_amount'] ?? 0);
            $minimum = (int) $quote['deposit_due'];
            $maximum = (int) $quote['booking_total'];
            if ($custom < $minimum) {
                throw new \InvalidArgumentException('Pay at least the '.(int) $settings->deposit_percent.'% deposit ('.number_format($minimum).' FCFA).');
            }
            if ($custom > $maximum) {
                throw new \InvalidArgumentException('That amount is above the total ('.number_format($maximum).' FCFA).');
            }
            $amountDue = $custom;
        } else {
            $amountDue = app(CothPricing::class)->amountDue($quote, $payChoice);
        }

        return DB::transaction(function () use ($table, $dates, $guest, $settings, $quote, $payChoice, $amountDue, $extraChairs) {
            if (! $table->is_active) {
                throw new \InvalidArgumentException('This table is not available on the selected date.');
            }
            $slots = [];
            foreach ($dates as $day) {
                $slot = CothSlot::where('coth_table_id', $table->id)
                    ->whereDate('event_date', $day)
                    ->lockForUpdate()
                    ->first();
                if (! $slot || ! $slot->is_enabled || $slot->status !== 'open') {
                    throw new \InvalidArgumentException('This table is not available on every selected day.');
                }
                $slot->status = 'held';
                $slot->save();
                $slots[] = $slot;
                if ($extraChairs > 0) {
                    $this->reserveChairHold($day, $extraChairs);
                }
            }

            $count = CothReservation::count() + 1;
            $first = $slots[0];
            $payload = [
                'reference' => 'COTH-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT),
                'public_token' => bin2hex(random_bytes(16)),
                'coth_slot_id' => $first->id,
                'coth_table_id' => $table->id,
                'event_date' => $dates[0],
                'customer_name' => $guest['name'],
                'phone' => WhatsAppPhone::normalize($guest['phone']),
                'email' => isset($guest['email']) ? $guest['email'] : null,
                'list_price' => $quote['list_price'],
                'discount_amount' => $quote['discount_amount'],
                'final_price' => $quote['final_price'],
                'deposit_due' => $quote['deposit_due'],
                'extra_chairs_qty' => $quote['extra_chairs_qty'],
                'extra_chair_rate' => $quote['extra_chair_rate'] ?: null,
                'extra_chairs_amount' => $quote['extra_chairs_amount'],
                'booking_total' => $quote['booking_total'],
                'pay_choice' => $payChoice,
                'amount_due' => $amountDue,
                'amount_paid' => 0,
                'balance' => $quote['booking_total'],
                'status' => 'held',
                'payment_status' => 'unpaid',
                'hold_expires_at' => now()->addMinutes((int) $settings->hold_minutes),
                'terms_accepted_at' => now(),
            ];
            if (Schema::hasColumn('coth_reservations', 'nights')) {
                $payload['nights'] = count($dates);
                $payload['stay_discount_percent'] = (int) $quote['stay_percent'];
            }
            $reservation = CothReservation::create($payload);
            if (Schema::hasTable('coth_reservation_days')) {
                $now = now();
                foreach ($slots as $held) {
                    DB::table('coth_reservation_days')->insert([
                        'coth_reservation_id' => $reservation->id,
                        'coth_slot_id' => $held->id,
                        'event_date' => $held->event_date->toDateString(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            return $reservation;
        });
    }

    /**
     * The next event dates from $start, in order. Closed days are skipped
     * and are not part of the stay.
     */
    public function consecutiveDates($start, $nights)
    {
        $nights = (int) $nights;
        $open = app(CothCatalog::class)->eventDates();
        $startDay = Carbon::parse($start)->toDateString();
        $index = array_search($startDay, $open, true);
        if ($index === false || $nights < 1) {
            return [];
        }

        return array_slice($open, $index, $nights);
    }

    /**
     * How far this table can be held from $start while every day stays open.
     * Each entry is nights, percent, end date, and a short label.
     */
    public function stayChoices(CothTable $table, $start)
    {
        $choices = [];
        for ($nights = 1; $nights <= 8; $nights++) {
            $dates = $this->consecutiveDates($start, $nights);
            if (count($dates) !== $nights) {
                break;
            }
            $free = true;
            foreach ($dates as $day) {
                $slot = CothSlot::where('coth_table_id', $table->id)->whereDate('event_date', $day)->first();
                if (! $slot || ! $slot->is_enabled || $slot->status !== 'open') {
                    $free = false;
                    break;
                }
            }
            if (! $free) {
                break;
            }
            $startLabel = Carbon::parse($dates[0])->format('j M');
            $endLabel = Carbon::parse($dates[$nights - 1])->format('j M');
            $choices[] = [
                'nights' => $nights,
                'percent' => app(CothPricing::class)->stayPercent($nights),
                'end' => $dates[$nights - 1],
                'label' => $nights === 1 ? $startLabel : $startLabel.' – '.$endLabel,
            ];
        }

        return $choices;
    }

    /**
     * Available extra chairs for a date. Returns null when there's no cap.
     */
    public function sharedChairsAvailable($eventDate)
    {
        $row = CothExtraChairInventory::whereDate('event_date', Carbon::parse($eventDate)->toDateString())->first();
        if (! $row || $row->total_capacity === null) {
            return null;
        }

        return (int) $row->available();
    }

    /**
     * Row-lock the shared chair inventory for a date; grow $held atomically.
     * No-op if there's no row for the date.
     * Caller is already inside a DB::transaction.
     */
    protected function reserveChairHold($date, $qty)
    {
        $row = CothExtraChairInventory::whereDate('event_date', $date)->lockForUpdate()->first();
        if (! $row) {
            return;
        }
        if ($row->total_capacity !== null) {
            $avail = (int) $row->total_capacity - (int) $row->held - (int) $row->booked;
            if ($qty > $avail) {
                throw new \InvalidArgumentException('Only '.$avail.' extra chair(s) are left for this date.');
            }
        }
        $row->held = (int) $row->held + $qty;
        $row->save();
    }

    /**
     * Release a reservation's chair hold back to the shared pool. Idempotent.
     * Caller is inside a transaction.
     */
    protected function releaseChairHold(CothReservation $reservation)
    {
        $qty = (int) ($reservation->extra_chairs_qty ?? 0);
        if ($qty <= 0) {
            return;
        }
        foreach ($this->stayDates($reservation) as $day) {
            $row = CothExtraChairInventory::whereDate('event_date', $day)->lockForUpdate()->first();
            if (! $row) {
                continue;
            }
            $row->held = max(0, (int) $row->held - $qty);
            $row->save();
        }
    }

    /**
     * Move chair hold -> booked when the reservation confirms.
     * Caller is inside a transaction.
     */
    protected function confirmChairHold(CothReservation $reservation)
    {
        $qty = (int) ($reservation->extra_chairs_qty ?? 0);
        if ($qty <= 0) {
            return;
        }
        foreach ($this->stayDates($reservation) as $day) {
            $row = CothExtraChairInventory::whereDate('event_date', $day)->lockForUpdate()->first();
            if (! $row) {
                continue;
            }
            $row->held = max(0, (int) $row->held - $qty);
            $row->booked = (int) $row->booked + $qty;
            $row->save();
        }
    }

    /**
     * Every date covered by the reservation. Older rows only have the start date.
     */
    public function stayDates(CothReservation $reservation)
    {
        $dates = [];
        if (Schema::hasTable('coth_reservation_days')) {
            $rows = DB::table('coth_reservation_days')
                ->where('coth_reservation_id', $reservation->id)
                ->orderBy('event_date')
                ->pluck('event_date');
            foreach ($rows as $day) {
                $dates[] = Carbon::parse($day)->toDateString();
            }
        }
        if (! count($dates) && $reservation->event_date) {
            $dates[] = $reservation->event_date->toDateString();
        }

        return array_values(array_unique($dates));
    }

    /**
     * Slot ids held or booked by this reservation.
     */
    public function staySlotIds(CothReservation $reservation)
    {
        $ids = [(int) $reservation->coth_slot_id];
        if (Schema::hasTable('coth_reservation_days')) {
            $more = DB::table('coth_reservation_days')
                ->where('coth_reservation_id', $reservation->id)
                ->pluck('coth_slot_id');
            foreach ($more as $id) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * Move every slot that is currently $from to $to.
     */
    protected function setSlotStatus(CothReservation $reservation, $from, $to)
    {
        foreach ($this->staySlotIds($reservation) as $slotId) {
            $slot = CothSlot::where('id', $slotId)->lockForUpdate()->first();
            if ($slot && $slot->status === $from) {
                $slot->status = $to;
                $slot->save();
            }
        }
    }

    protected function holdIsLive(CothReservation $row)
    {
        if ($row->status !== 'held' || ! $row->hold_expires_at || ! $row->hold_expires_at->isFuture()) {
            return false;
        }
        $ids = $this->staySlotIds($row);
        if (! count($ids)) {
            return false;
        }
        foreach ($ids as $slotId) {
            $slot = CothSlot::where('id', $slotId)->lockForUpdate()->first();
            if (! $slot || $slot->status !== 'held') {
                return false;
            }
        }

        return true;
    }

    /**
     * Apply a provider result. Confirms only a live hold paid at or above
     * the required amount (deposit for the deposit choice, full booking
     * total for the full choice).
     * Returns confirmed, duplicate, flagged, or failed.
     */
    public function applyGateway(CothReservation $reservation, $successful, $amount, $reference, $method)
    {
        $reference = trim((string) $reference);
        $amount = (int) $amount;

        return DB::transaction(function () use ($reservation, $successful, $amount, $reference, $method) {
            $row = CothReservation::where('id', $reservation->id)->lockForUpdate()->first();
            if (! $row) {
                return 'failed';
            }
            if ($row->status === 'confirmed' && $reference !== '' && $row->gateway_reference === $reference) {
                return 'duplicate';
            }
            if ($row->status === 'confirmed') {
                return 'duplicate';
            }
            if (! $successful) {
                if ($row->status === 'held') {
                    $row->payment_status = 'failed';
                    $row->save();
                }

                return 'failed';
            }

            $holdLive = $this->holdIsLive($row);
            $required = (int) $row->amount_due;
            if ($required <= 0) {
                $required = $row->pay_choice === 'full' ? (int) $row->booking_total : (int) $row->deposit_due;
            }
            if (! $holdLive || $amount < $required) {
                $row->payment_status = 'needs_reconciliation';
                $row->gateway_reference = $reference !== '' ? $reference : $row->gateway_reference;
                $row->pay_method = $method;
                $row->staff_note = trim((string) $row->staff_note."\nPayment ".$amount.' arrived outside an active hold or below the required amount.');
                $row->save();

                return 'flagged';
            }

            $row->status = 'confirmed';
            $row->amount_paid = $amount;
            $row->balance = (int) $row->booking_total - $amount;
            $row->payment_status = $row->balance <= 0 ? 'paid' : 'partial';
            $row->gateway_reference = $reference;
            $row->pay_method = $method;
            $row->customer_id = $this->customerId($row);
            $row->sale_id = $this->recordSale($row);
            $row->save();
            $this->setSlotStatus($row, 'held', 'booked');
            $this->confirmChairHold($row);
            $this->notify($row);

            return 'confirmed';
        });
    }

    /**
     * Confirm a verified hold without taking money. The full amount stays due.
     */
    public function confirmPayLater(CothReservation $reservation)
    {
        return DB::transaction(function () use ($reservation) {
            $row = CothReservation::where('id', $reservation->id)->lockForUpdate()->first();
            if (! $row) {
                throw new \InvalidArgumentException('This reservation could not be found.');
            }
            if ($row->status === 'confirmed') {
                return $row;
            }
            if ($row->status !== 'held' || ! $row->phone_verified_at) {
                throw new \InvalidArgumentException('Verify the phone number before the booking can be completed.');
            }
            if ($row->hold_expires_at && $row->hold_expires_at->isPast()) {
                throw new \InvalidArgumentException('This hold has expired. Choose the table again.');
            }
            if (! $this->holdIsLive($row)) {
                throw new \InvalidArgumentException('This table is no longer held.');
            }

            $row->pay_choice = 'later';
            $row->amount_due = 0;
            $row->amount_paid = 0;
            $row->balance = (int) $row->booking_total;
            $row->payment_status = 'pay_later';
            $row->pay_method = 'later';
            $row->status = 'confirmed';
            $row->customer_id = $this->customerId($row);
            $row->sale_id = $this->recordSale($row);
            $row->save();
            $this->setSlotStatus($row, 'held', 'booked');
            $this->confirmChairHold($row);
            $this->notify($row);

            return $row;
        });
    }

    protected function customerId(CothReservation $reservation)
    {
        if (! Schema::hasTable('customers')) {
            return null;
        }
        try {
            $phone = $reservation->phone;
            $customer = Customer::where('phone_number', $phone)->first();
            if (! $customer) {
                $group = Schema::hasTable('customer_groups') ? CustomerGroup::query()->orderBy('id')->first() : null;
                $customer = Customer::create([
                    'customer_group_id' => $group ? $group->id : 1,
                    'name' => $reservation->customer_name,
                    'phone_number' => $phone,
                    'email' => $reservation->email,
                    'address' => 'Chill on the Hill',
                    'city' => 'Bamenda',
                    'is_active' => 1,
                ]);
            }

            return $customer->id;
        } catch (\Throwable $e) {
            \Log::warning('coth customer: '.$e->getMessage());

            return null;
        }
    }

    protected function recordSale(CothReservation $reservation)
    {
        if (! Schema::hasTable('sales') || ! Schema::hasTable('warehouses') || ! Schema::hasTable('billers')) {
            return null;
        }
        try {
            $warehouse = Warehouse::query()->orderBy('id')->first();
            $biller = Biller::query()->orderBy('id')->first();
            if (! $warehouse || ! $biller || ! $reservation->customer_id) {
                return null;
            }
            $totalDiscount = (int) $reservation->discount_amount;
            $itemCount = 1 + ((int) $reservation->extra_chairs_qty > 0 ? 1 : 0);
            $totalQty = 1 + (int) $reservation->extra_chairs_qty;
            $grand = (int) $reservation->booking_total;
            $sale = Sale::create([
                'reference_no' => 'coth-'.$reservation->reference,
                'customer_id' => $reservation->customer_id,
                'warehouse_id' => $warehouse->id,
                'biller_id' => $biller->id,
                'item' => $itemCount,
                'total_qty' => $totalQty,
                'total_discount' => $totalDiscount,
                'total_tax' => 0,
                'total_price' => $grand,
                'order_tax_rate' => 0,
                'order_tax' => 0,
                'order_discount' => 0,
                'shipping_cost' => 0,
                'grand_total' => $grand,
                'sale_status' => 1,
                'payment_status' => $reservation->balance <= 0 ? 4 : ((int) $reservation->amount_paid > 0 ? 3 : 2),
                'paid_amount' => $reservation->amount_paid,
                'paid_by_id' => $reservation->pay_method === 'card' ? 3 : 8,
                'sale_note' => 'Chill on the Hill '.$reservation->reference.' · '.$reservation->stayLabel()
                    .((int) $reservation->extra_chairs_qty > 0
                        ? ' · '.$reservation->extra_chairs_qty.' extra chair(s) @ '.number_format((int) $reservation->extra_chair_rate).' FCFA'
                        : ''),
            ]);
            if (Schema::hasTable('payments') && (int) $reservation->amount_paid > 0) {
                try {
                    $payment = new Payment();
                    $payment->sale_id = $sale->id;
                    $accountId = Schema::hasTable('accounts') ? optional(\App\Account::query()->orderBy('id')->first())->id : null;
                    $payment->account_id = $accountId ?: 1;
                    $payment->payment_reference = 'coth-'.date('YmdHis');
                    $payment->amount = $reservation->amount_paid;
                    $payment->change = 0;
                    $payment->paying_method = $reservation->pay_method === 'card' ? 'Credit Card' : 'Momo/Orange';
                    $payment->payment_note = ($reservation->pay_method === 'card' ? 'stripe:' : 'campay:').$reservation->gateway_reference;
                    $payment->save();
                } catch (\Throwable $e) {
                    \Log::warning('coth payment row: '.$e->getMessage());
                }
            }

            return $sale->id;
        } catch (\Throwable $e) {
            \Log::warning('coth sale: '.$e->getMessage());

            return null;
        }
    }

    protected function notify(CothReservation $reservation)
    {
        try {
            $table = $reservation->table ?: CothTable::find($reservation->coth_table_id);
            $section = $table && $table->section ? $table->section->name : '';
            $extraQty = (int) $reservation->extra_chairs_qty;
            $totalGuests = ($table ? (int) $table->capacity : 0) + $extraQty;
            $lines = [
                '*Chill on the Hill reservation*',
                '• Reference: '.$reservation->reference,
                '• Name: '.$reservation->customer_name,
                '• Date: '.$reservation->stayLabel(),
                '• Section: '.$section,
                '• Table: '.($table ? $table->code : $reservation->coth_table_id).' · '.($table ? $table->capacity : '').' included seats',
            ];
            if ($extraQty > 0) {
                $lines[] = '• Extra chairs: '.$extraQty.' × '.number_format((int) $reservation->extra_chair_rate).' FCFA';
                $lines[] = '• Guests: '.$totalGuests.' ('.($table ? $table->capacity : 0).' included + '.$extraQty.' extra)';
            }
            $lines[] = '• Listed table: '.number_format((int) $reservation->list_price).' FCFA';
            if ((int) $reservation->discount_amount > 0) {
                $lines[] = '• Discount: '.number_format((int) $reservation->discount_amount).' FCFA';
            }
            $lines[] = '• Final table: '.number_format((int) $reservation->final_price).' FCFA';
            if ($extraQty > 0) {
                $lines[] = '• Chairs total: '.number_format((int) $reservation->extra_chairs_amount).' FCFA';
            }
            $lines[] = '• Booking total: '.number_format((int) $reservation->booking_total).' FCFA';
            $lines[] = '• Paid: '.number_format((int) $reservation->amount_paid).' FCFA';
            $lines[] = '• Balance: '.number_format((int) $reservation->balance).' FCFA';
            $lines[] = '• Payment: '.($reservation->pay_choice === 'later' ? 'Pay later' : $reservation->payment_status);
            if ($reservation->pay_choice === 'later') {
                $lines[] = '• Nothing was paid today. The full amount is still due.';
            } else {
                $lines[] = '• The deposit is non-refundable.';
            }
            $lines[] = '• View: '.$reservation->publicUrl();

            app(\App\Services\Messaging\NotificationRouter::class)
                ->sendWhatsAppTextWithLink($reservation->phone, implode("\n", $lines));
        } catch (\Throwable $e) {
            \Log::warning('coth notify: '.$e->getMessage());
        }
    }
}
