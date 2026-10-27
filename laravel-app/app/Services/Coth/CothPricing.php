<?php

namespace App\Services\Coth;

use App\CothSetting;
use Carbon\Carbon;

class CothPricing
{
    public function quote($listPrice, $eventDate, CothSetting $settings)
    {
        $list = (int) $listPrice;
        $date = Carbon::parse($eventDate)->toDateString();
        $discount = in_array($date, $settings->discountDateList(), true)
            ? intdiv($list * (int) $settings->discount_percent, 100)
            : 0;
        $final = $list - $discount;
        $deposit = intdiv($final * (int) $settings->deposit_percent, 100);

        return [
            'list_price' => $list,
            'discount_amount' => $discount,
            'final_price' => $final,
            'deposit_due' => $deposit,
            'discounted' => $discount > 0,
        ];
    }

    public function amountDue(array $quote, $payChoice)
    {
        return $payChoice === 'full' ? (int) $quote['final_price'] : (int) $quote['deposit_due'];
    }
}
