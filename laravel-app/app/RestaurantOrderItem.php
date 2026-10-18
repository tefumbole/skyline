<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RestaurantOrderItem extends Model
{
    protected $fillable = [
        'restaurant_order_id', 'product_sale_id', 'product_id', 'product_name',
        'department', 'qty', 'status', 'customer_comment', 'internal_note',
        'accepted_by', 'accepted_at', 'estimate_minutes', 'original_estimate_minutes',
        'expected_ready_at', 'ready_by', 'ready_at', 'delivered_by', 'delivered_at',
        'attempt_no', 'remake_of_id',
    ];

    protected $dates = [
        'accepted_at', 'expected_ready_at', 'ready_at', 'delivered_at',
    ];

    public function order()
    {
        return $this->belongsTo('App\RestaurantOrder', 'restaurant_order_id');
    }

    public function acceptedBy()
    {
        return $this->belongsTo('App\User', 'accepted_by');
    }

    public function readyBy()
    {
        return $this->belongsTo('App\User', 'ready_by');
    }

    public function deliveredBy()
    {
        return $this->belongsTo('App\User', 'delivered_by');
    }
}
