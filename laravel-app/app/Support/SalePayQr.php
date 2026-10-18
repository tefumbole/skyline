<?php

namespace App\Support;

use App\Sale;

class SalePayQr
{
    public static function token(Sale $sale)
    {
        return substr(hash_hmac(
            'sha256',
            'sale-pay|'.$sale->id.'|'.$sale->reference_no,
            (string) config('app.key')
        ), 0, 20);
    }

    public static function payUrl(Sale $sale)
    {
        return url('/pay/invoice/'.$sale->id.'/'.self::token($sale));
    }

    public static function isValid(Sale $sale, $token)
    {
        return hash_equals(self::token($sale), (string) $token);
    }
}
