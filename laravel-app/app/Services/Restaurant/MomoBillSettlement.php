<?php

namespace App\Services\Restaurant;

use App\Customer;
use App\Deposit;
use App\Payment;
use App\Sale;

class MomoBillSettlement
{
    /**
     * A confirmed debit covers the bill when the phone paid the total,
     * or only the operator fee is missing from the amount that landed.
     */
    public static function covers($expected, $received)
    {
        $expected = (int) round((float) $expected);
        $received = (int) round((float) $received);
        if ($expected < 1 || $received < 1) {
            return false;
        }
        if (abs($expected - $received) <= 1) {
            return true;
        }
        if ($received > $expected) {
            return false;
        }
        $allowance = max(100, (int) round($expected * 0.03));

        return ($expected - $received) <= $allowance;
    }

    public static function coversEither($expected, $charged, $landed)
    {
        return self::covers($expected, $charged) || self::covers($expected, $landed);
    }

    /**
     * Apply a received Mobile Money amount to the client named on the request.
     * The name registered on the phone does not have to be that client.
     */
    public function settleDescribedMoney($note, $reference, $received, $userId = null)
    {
        $reference = trim((string) $reference);
        $received = (float) $received;
        if ($reference === '' || $received < 1) {
            return null;
        }
        $existing = $this->saleForReference($reference);
        if ($existing) {
            return $existing;
        }
        $sale = $this->matchingSale($note, $received);
        if (! $sale) {
            return null;
        }
        $due = max(0, (float) $sale->grand_total - (float) $sale->paid_amount);
        if ($due < 0.5) {
            return $sale;
        }
        $result = RestaurantPayments::recordGateway($sale, $due, $reference, $userId ?: $sale->user_id);

        return in_array($result, ['recorded', 'duplicate', 'paid'], true) ? $sale->fresh() : null;
    }

    /**
     * Move money that was stored under the phone's registered name onto the bill it paid.
     */
    public function settleNotedDeposits()
    {
        $rows = Deposit::with('customer')
            ->where('status', 1)
            ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-3 days')))
            ->where('note', 'like', 'Skyline %')
            ->whereNotNull('campay_reference')
            ->orderByDesc('id')
            ->limit(40)
            ->get();
        foreach ($rows as $deposit) {
            $sale = $this->settleDescribedMoney($deposit->note, $deposit->campay_reference, $deposit->amount, $deposit->user_id);
            if (! $sale || (int) $deposit->customer_id === (int) $sale->customer_id) {
                continue;
            }
            $holder = trim((string) optional($deposit->customer)->name);
            $deposit->customer_id = $sale->customer_id;
            if ($holder !== '' && stripos((string) $deposit->note, $holder) === false) {
                $deposit->note = substr(trim($deposit->note.' · MoMo '.$holder), 0, 180);
            }
            $deposit->save();
        }
    }

    protected function matchingSale($note, $received)
    {
        $name = trim((string) preg_replace('/^Skyline\s*\/?\s*/i', '', (string) $note));
        $name = trim(preg_replace('/\s+·.*$/', '', $name));
        if ($name === '') {
            return null;
        }
        $customers = Customer::whereRaw('LOWER(name) = ?', [strtolower($name)])->get();
        $best = null;
        $bestGap = null;
        foreach ($customers as $customer) {
            $sales = Sale::where('customer_id', $customer->id)
                ->where('payment_status', '!=', 4)
                ->orderBy('id')
                ->get();
            foreach ($sales as $sale) {
                $due = max(0, (float) $sale->grand_total - (float) $sale->paid_amount);
                if (! self::covers($due, $received)) {
                    continue;
                }
                $gap = abs($due - $received);
                if ($bestGap === null || $gap < $bestGap) {
                    $best = $sale;
                    $bestGap = $gap;
                }
            }
        }

        return $best;
    }

    protected function saleForReference($reference)
    {
        $payment = Payment::where('payment_note', 'like', '%campay:'.$reference.'%')->first();
        if (! $payment) {
            return null;
        }

        return Sale::find($payment->sale_id);
    }
}
