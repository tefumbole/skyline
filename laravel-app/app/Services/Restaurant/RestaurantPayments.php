<?php

namespace App\Services\Restaurant;

use App\Payment;
use App\Sale;

class RestaurantPayments
{
    public static function gatewayNote($reference)
    {
        $reference = trim((string) $reference);

        return $reference === '' ? '' : 'campay:'.$reference;
    }

    public static function alreadyRecorded($saleId, $reference)
    {
        $note = self::gatewayNote($reference);
        if ($note === '' || ! $saleId) {
            return false;
        }

        return Payment::where('sale_id', $saleId)->where('payment_note', 'like', '%'.$note.'%')->exists();
    }

    /**
     * Record a Campay success once. Returns recorded, duplicate, paid, or failed.
     */
    public static function recordGateway(Sale $sale, $amount, $reference, $userId = null)
    {
        $amount = (float) $amount;
        if ($amount <= 0) {
            return 'failed';
        }
        if (self::alreadyRecorded($sale->id, $reference)) {
            return 'duplicate';
        }
        if ((int) $sale->payment_status === 4 && (float) $sale->paid_amount > 0.009) {
            return 'paid';
        }

        $sale->paid_amount = (float) $sale->paid_amount + $amount;
        $balance = (float) $sale->grand_total - (float) $sale->paid_amount;
        if ($balance <= 0) {
            $sale->payment_status = 4;
        } elseif ((float) $sale->paid_amount > 0) {
            $sale->payment_status = 3;
        } else {
            $sale->payment_status = 2;
        }
        $sale->save();

        $payment = new Payment();
        $payment->user_id = $userId;
        $payment->sale_id = $sale->id;
        $payment->account_id = 1;
        $payment->payment_reference = 'spr-'.date('Ymd').'-'.date('His');
        $payment->amount = $amount;
        $payment->change = 0;
        $payment->paying_method = 'Momo/Orange';
        $payment->payment_note = self::gatewayNote($reference);
        $payment->save();

        return 'recorded';
    }
}
