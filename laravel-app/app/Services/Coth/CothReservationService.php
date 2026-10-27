<?php

namespace App\Services\Coth;

use App\Biller;
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
            'discount_percent' => 20,
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
                $slot = CothSlot::where('id', $row->coth_slot_id)->lockForUpdate()->first();
                if ($slot && $slot->status === 'held') {
                    $slot->status = 'open';
                    $slot->save();
                }
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
        $payChoice = (isset($guest['pay_choice']) && $guest['pay_choice'] === 'full') ? 'full' : 'deposit';
        $quote = app(CothPricing::class)->quote($table->price, $date, $settings);
        $amountDue = app(CothPricing::class)->amountDue($quote, $payChoice);

        return DB::transaction(function () use ($table, $date, $guest, $settings, $quote, $payChoice, $amountDue) {
            $slot = CothSlot::where('coth_table_id', $table->id)
                ->whereDate('event_date', $date)
                ->lockForUpdate()
                ->first();
            if (! $slot || ! $slot->is_enabled || $slot->status !== 'open' || ! $table->is_active) {
                throw new \InvalidArgumentException('This table is not available on the selected date.');
            }
            $slot->status = 'held';
            $slot->save();

            $count = CothReservation::count() + 1;

            return CothReservation::create([
                'reference' => 'COTH-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT),
                'public_token' => bin2hex(random_bytes(16)),
                'coth_slot_id' => $slot->id,
                'coth_table_id' => $table->id,
                'event_date' => $date,
                'customer_name' => $guest['name'],
                'phone' => WhatsAppPhone::normalize($guest['phone']),
                'email' => isset($guest['email']) ? $guest['email'] : null,
                'list_price' => $quote['list_price'],
                'discount_amount' => $quote['discount_amount'],
                'final_price' => $quote['final_price'],
                'deposit_due' => $quote['deposit_due'],
                'pay_choice' => $payChoice,
                'amount_due' => $amountDue,
                'amount_paid' => 0,
                'balance' => $quote['final_price'],
                'status' => 'held',
                'payment_status' => 'unpaid',
                'hold_expires_at' => now()->addMinutes((int) $settings->hold_minutes),
                'terms_accepted_at' => now(),
            ]);
        });
    }

    /**
     * Apply a provider result. Confirms only a live hold paid at or above the stored deposit.
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

            $slot = CothSlot::where('id', $row->coth_slot_id)->lockForUpdate()->first();
            $holdLive = $row->status === 'held'
                && $row->hold_expires_at
                && $row->hold_expires_at->isFuture()
                && $slot
                && $slot->status === 'held';
            if (! $holdLive || $amount < (int) $row->deposit_due) {
                $row->payment_status = 'needs_reconciliation';
                $row->gateway_reference = $reference !== '' ? $reference : $row->gateway_reference;
                $row->pay_method = $method;
                $row->staff_note = trim((string) $row->staff_note."\nPayment ".$amount.' arrived outside an active hold or below the deposit.');
                $row->save();

                return 'flagged';
            }

            $row->status = 'confirmed';
            $row->amount_paid = $amount;
            $row->balance = (int) $row->final_price - $amount;
            $row->payment_status = $row->balance <= 0 ? 'paid' : 'partial';
            $row->gateway_reference = $reference;
            $row->pay_method = $method;
            $row->customer_id = $this->customerId($row);
            $row->sale_id = $this->recordSale($row);
            $row->save();
            $slot->status = 'booked';
            $slot->save();
            $this->notify($row);

            return 'confirmed';
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
            $sale = Sale::create([
                'reference_no' => 'coth-'.$reservation->reference,
                'customer_id' => $reservation->customer_id,
                'warehouse_id' => $warehouse->id,
                'biller_id' => $biller->id,
                'item' => 1,
                'total_qty' => 1,
                'total_discount' => $reservation->discount_amount,
                'total_tax' => 0,
                'total_price' => $reservation->final_price,
                'order_tax_rate' => 0,
                'order_tax' => 0,
                'order_discount' => 0,
                'shipping_cost' => 0,
                'grand_total' => $reservation->final_price,
                'sale_status' => 1,
                'payment_status' => $reservation->balance <= 0 ? 4 : 3,
                'paid_amount' => $reservation->amount_paid,
                'paid_by_id' => $reservation->pay_method === 'card' ? 3 : 8,
                'sale_note' => 'Chill on the Hill '.$reservation->reference.' · '.$reservation->event_date->format('Y-m-d'),
            ]);
            if (Schema::hasTable('payments')) {
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
            $lines = [
                '*Chill on the Hill reservation*',
                '• Reference: '.$reservation->reference,
                '• Name: '.$reservation->customer_name,
                '• Date: '.$reservation->event_date->format('l, j F Y').' (one day)',
                '• Section: '.$section,
                '• Table: '.($table ? $table->code : $reservation->coth_table_id).' · '.($table ? $table->capacity : '').' seats',
                '• Listed: '.number_format((int) $reservation->list_price).' FCFA',
                '• Discount: '.number_format((int) $reservation->discount_amount).' FCFA',
                '• Final: '.number_format((int) $reservation->final_price).' FCFA',
                '• Paid: '.number_format((int) $reservation->amount_paid).' FCFA',
                '• Balance: '.number_format((int) $reservation->balance).' FCFA',
                '• Payment: '.$reservation->payment_status,
                '• The deposit is non-refundable.',
                '• View: '.$reservation->publicUrl(),
            ];
            app(\App\Services\Messaging\NotificationRouter::class)
                ->sendWhatsAppTextWithLink($reservation->phone, implode("\n", $lines));
        } catch (\Throwable $e) {
            \Log::warning('coth notify: '.$e->getMessage());
        }
    }
}
