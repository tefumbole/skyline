<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CothTable extends Model
{
    protected $fillable = [
        'coth_section_id', 'code', 'name', 'capacity', 'seating_style', 'price', 'is_active', 'pos_x', 'pos_y',
    ];

    public function section()
    {
        return $this->belongsTo(CothSection::class, 'coth_section_id');
    }

    public function slots()
    {
        return $this->hasMany(CothSlot::class);
    }

    public function isCouch()
    {
        return $this->seating_style === 'couch';
    }
}
