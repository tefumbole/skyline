<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MealRejection extends Model
{
    protected $fillable = [
        'restaurant_order_id', 'restaurant_order_item_id', 'qty', 'reason',
        'resolution', 'user_id', 'remake_item_id',
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
