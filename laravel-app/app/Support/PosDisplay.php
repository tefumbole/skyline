<?php

namespace App\Support;

class PosDisplay
{
    /**
     * Drop a trailing price that was typed into the product name.
     * "Prawns 7,000" at 7000 becomes "Prawns". Sizes such as "33cl" stay.
     */
    public static function name($name, $price)
    {
        $label = trim((string) $name);
        $amount = (float) $price;
        if ($label === '' || $amount <= 0) {
            return $label;
        }

        $plain = (string) (int) round($amount);
        $grouped = number_format((float) $plain, 0, '.', ',');
        $pattern = '/\s+(?:'.preg_quote($plain, '/').'|'.preg_quote($grouped, '/').')(?:\.00)?$/';
        $short = preg_replace($pattern, '', $label);

        return ($short !== null && $short !== '') ? $short : $label;
    }
}
