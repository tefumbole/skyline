<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RestaurantOrderEvent extends Model
{
    protected $fillable = [
        'restaurant_order_id', 'restaurant_order_item_id', 'action', 'user_id', 'note', 'meta',
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function item()
    {
        return $this->belongsTo('App\RestaurantOrderItem', 'restaurant_order_item_id');
    }
}
