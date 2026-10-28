<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CothSetting extends Model
{
    protected $fillable = [
        'deposit_percent', 'discount_percent', 'hold_minutes', 'discount_dates', 'discount_includes_addons', 'timezone', 'event_id',
    ];

    public function discountDateList()
    {
        $decoded = json_decode((string) $this->discount_dates, true);

        return is_array($decoded) ? $decoded : [];
    }
}
