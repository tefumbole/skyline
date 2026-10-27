<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CothSlot extends Model
{
    protected $fillable = ['coth_table_id', 'event_date', 'status', 'is_enabled'];

    protected $dates = ['event_date'];

    public function table()
    {
        return $this->belongsTo(CothTable::class, 'coth_table_id');
    }

    public function reservations()
    {
        return $this->hasMany(CothReservation::class);
    }
}
