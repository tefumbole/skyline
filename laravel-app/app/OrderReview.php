<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderReview extends Model
{
    protected $fillable = [
        'sale_id', 'guest_name', 'country', 'delivery_time', 'food_quality',
        'environment', 'services', 'comment',
    ];

    public function sale()
    {
        return $this->belongsTo('App\Sale');
    }
}
