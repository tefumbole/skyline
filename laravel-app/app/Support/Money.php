<?php

namespace App\Support;

class Money
{
    /**
     * Whole currency with thousands separators: 70000.00 becomes 70,000.
     */
    public static function format($amount)
    {
        if ($amount === null || $amount === '') {
            return '0';
        }
        $raw = str_replace([',', ' '], '', (string) $amount);
        if (! is_numeric($raw)) {
            return (string) $amount;
        }

        return number_format((int) round((float) $raw), 0, '.', ',');
    }
}
