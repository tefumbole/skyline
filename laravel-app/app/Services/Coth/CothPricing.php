<?php

namespace App\Services\Coth;

use App\CothSetting;
use App\CothTable;
use Carbon\Carbon;

class CothPricing
{
    /**
     * Resolve the per-chair rate for extra seats on this table.
     *   - Admin override on the table always wins.
     *   - Any 6-seat table: 75,000 FCFA (overrides section rules).
     *   - Outer Restaurant (non-6-seat): 100,000 FCFA.
     *   - Yard, Hall, and Pool (non-6-seat): 50,000 FCFA.
     */
    public function extraChairRate(CothTable $table = null)
    {
        if (! $table) {
            return null;
        }
        if ($table->extra_chair_rate !== null && $table->extra_chair_rate !== '') {
            return (int) $table->extra_chair_rate;
        }
        if ((int) $table->capacity === 6) {
            return 75000;
        }
        $section = optional($table->section)->code;
        if ($section === 'outer') {
            return 100000;
        }
        if ($section === 'yard' || $section === 'hall' || $section === 'pool') {
            return 50000;
        }

        return null;
    }

    /**
     * How many extra chairs a guest may add. A table with a rate always
     * allows some, even when the admin max has not been set yet.
     */
    public function extraChairLimit(CothTable $table = null)
    {
        if (! $table || $this->extraChairRate($table) === null) {
            return 0;
        }
        $max = (int) $table->max_extra_chairs;

        return $max > 0 ? $max : 4;
    }

    /**
     * Price a reservation. Includes extra-chair maths when a table + count
     * are supplied. Discount applies to the base table only unless
     * $settings->discount_includes_addons is on.
     *
     * Returns an associative array with:
     *   list_price, discount_amount, final_price, discounted,
     *   extra_chairs_qty, extra_chair_rate, extra_chairs_amount,
     *   booking_total, deposit_due
     */
    /**
     * Discount for a stay longer than one day.
     * 2 days 5%, then 5 points more for each extra day, up to 8 days at 35%.
     */
    public function stayPercent($days)
    {
        $map = array(
            2 => 5,
            3 => 10,
            4 => 15,
            5 => 20,
            6 => 25,
            7 => 30,
            8 => 35,
        );
        $days = (int) $days;

        return isset($map[$days]) ? $map[$days] : 0;
    }

    /**
     * Price one table across consecutive days. One day keeps the date discount.
     * Two or more days use the stay discount on the table and on extra chairs.
     */
    public function quoteStay(CothTable $table, array $dates, CothSetting $settings, $extraChairs = 0)
    {
        $days = count($dates);
        if ($days < 2) {
            $day = isset($dates[0]) ? $dates[0] : null;
            $quote = $this->quote($table->price, $day, $settings, $table, $extraChairs);
            $quote['nights'] = 1;
            $quote['stay_percent'] = 0;

            return $quote;
        }

        $list = (int) $table->price * $days;
        $percent = $this->stayPercent($days);
        $tableDiscount = intdiv($list * $percent, 100);
        $tableFinal = $list - $tableDiscount;

        $qty = max(0, (int) $extraChairs);
        $rate = $qty > 0 ? $this->extraChairRate($table) : null;
        $chairsGross = ($rate !== null) ? ($rate * $qty * $days) : 0;
        $chairsDiscount = intdiv($chairsGross * $percent, 100);
        $chairsAmount = $chairsGross - $chairsDiscount;
        $bookingTotal = $tableFinal + $chairsAmount;
        $depositPct = (int) $settings->deposit_percent;
        $deposit = (int) ceil($bookingTotal * $depositPct / 100);

        return [
            'list_price' => $list,
            'discount_amount' => $tableDiscount,
            'final_price' => $tableFinal,
            'discounted' => $tableDiscount > 0,
            'extra_chairs_qty' => $qty,
            'extra_chair_rate' => $rate === null ? 0 : (int) $rate,
            'extra_chairs_amount' => $chairsAmount,
            'extra_chairs_discount' => $chairsDiscount,
            'booking_total' => $bookingTotal,
            'deposit_due' => $deposit,
            'nights' => $days,
            'stay_percent' => $percent,
        ];
    }

    public function quote($listPrice, $eventDate, CothSetting $settings, CothTable $table = null, $extraChairs = 0)
    {
        $list = (int) $listPrice;
        $date = Carbon::parse($eventDate)->toDateString();
        $isDiscountDate = in_array($date, $settings->discountDateList(), true);
        $discountPct = (int) $settings->discount_percent;
        $tableDiscount = $isDiscountDate ? intdiv($list * $discountPct, 100) : 0;
        $tableFinal = $list - $tableDiscount;

        $qty = max(0, (int) $extraChairs);
        $rate = $qty > 0 ? $this->extraChairRate($table) : null;
        $chairsGross = ($rate !== null) ? ($rate * $qty) : 0;
        $chairsDiscount = ($isDiscountDate && (int) ($settings->discount_includes_addons ?? 0) === 1)
            ? intdiv($chairsGross * $discountPct, 100)
            : 0;
        $chairsAmount = $chairsGross - $chairsDiscount;

        $bookingTotal = $tableFinal + $chairsAmount;
        $depositPct = (int) $settings->deposit_percent;
        $deposit = (int) ceil($bookingTotal * $depositPct / 100);

        return [
            'list_price' => $list,
            'discount_amount' => $tableDiscount,
            'final_price' => $tableFinal,
            'discounted' => $tableDiscount > 0,
            'extra_chairs_qty' => $qty,
            'extra_chair_rate' => $rate === null ? 0 : (int) $rate,
            'extra_chairs_amount' => $chairsAmount,
            'extra_chairs_discount' => $chairsDiscount,
            'booking_total' => $bookingTotal,
            'deposit_due' => $deposit,
        ];
    }

    /**
     * Amount a specific pay choice requires to confirm the hold.
     * Full means the whole booking total; deposit means 20% of the total.
     */
    public function amountDue(array $quote, $payChoice)
    {
        return $payChoice === 'full' ? (int) $quote['booking_total'] : (int) $quote['deposit_due'];
    }
}
