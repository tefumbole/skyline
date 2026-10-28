<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CothExtraChairInventory extends Model
{
    protected $table = 'coth_extra_chair_inventory';

    protected $fillable = ['event_date', 'total_capacity', 'held', 'booked'];

    protected $dates = ['event_date'];

    public function available()
    {
        if ($this->total_capacity === null) {
            return PHP_INT_MAX;
        }

        return max(0, (int) $this->total_capacity - (int) $this->held - (int) $this->booked);
    }
}
