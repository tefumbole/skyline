<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CothTable extends Model
{
    protected $fillable = [
        'coth_section_id', 'code', 'name', 'capacity', 'seating_style', 'price', 'is_active', 'pos_x', 'pos_y',
        'visual_preset', 'is_city_view', 'is_high_table',
        'tabletop_material', 'tabletop_color', 'upholstery_color', 'cushion_colors', 'trim_color',
        'allow_extra_chairs', 'max_extra_chairs', 'extra_chair_rate', 'extra_chair_furniture_type',
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
