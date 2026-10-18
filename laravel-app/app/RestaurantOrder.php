<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RestaurantOrder extends Model
{
    protected $fillable = [
        'sale_id', 'source', 'service_type', 'restaurant_table_id',
        'placed_by_user_id', 'waitress_user_id', 'customer_comment',
        'internal_note', 'token', 'checkout_token', 'review_sent_at',
    ];

    public function sale()
    {
        return $this->belongsTo('App\Sale');
    }

    public function diningTable()
    {
        return $this->belongsTo('App\RestaurantTable', 'restaurant_table_id');
    }

    public function items()
    {
        return $this->hasMany('App\RestaurantOrderItem');
    }

    public function events()
    {
        return $this->hasMany('App\RestaurantOrderEvent');
    }

    public function placedBy()
    {
        return $this->belongsTo('App\User', 'placed_by_user_id');
    }

    public function waitress()
    {
        return $this->belongsTo('App\User', 'waitress_user_id');
    }

    public function trackUrl()
    {
        return url('/order-track/'.$this->sale_id.'/'.$this->token);
    }

    public function reviewUrl()
    {
        return url('/order-review/'.$this->sale_id.'/'.$this->token);
    }

    public function serviceLabel()
    {
        if ($this->service_type === 'dine_in') {
            $table = $this->diningTable;

            return $table ? 'Dine In · '.$table->name : 'Dine In';
        }

        return 'Take Away';
    }

    public function activeItems()
    {
        return $this->items->filter(function ($item) {
            return $item->status !== 'cancelled';
        });
    }

    public function isFulfilled()
    {
        $active = $this->activeItems();
        if ($active->isEmpty()) {
            return $this->items->count() === 0;
        }

        return $active->every(function ($item) {
            return $item->status === 'delivered';
        });
    }

    public function partiallyDelivered()
    {
        $active = $this->activeItems();
        $delivered = $active->where('status', 'delivered')->count();

        return $delivered > 0 && $delivered < $active->count();
    }
}
