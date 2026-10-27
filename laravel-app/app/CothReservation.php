<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CothReservation extends Model
{
    protected $fillable = [
        'reference', 'public_token', 'coth_slot_id', 'coth_table_id', 'event_date',
        'customer_id', 'customer_name', 'phone', 'email',
        'list_price', 'discount_amount', 'final_price', 'deposit_due',
        'pay_choice', 'amount_due', 'amount_paid', 'balance',
        'status', 'payment_status', 'hold_expires_at', 'phone_verified_at', 'terms_accepted_at',
        'sale_id', 'gateway_reference', 'stripe_session_id', 'pay_method', 'staff_note',
    ];

    protected $dates = ['event_date', 'hold_expires_at', 'phone_verified_at', 'terms_accepted_at'];

    public function table()
    {
        return $this->belongsTo(CothTable::class, 'coth_table_id');
    }

    public function slot()
    {
        return $this->belongsTo(CothSlot::class, 'coth_slot_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function publicUrl()
    {
        return url('/chill-on-the-hill/reservation/'.$this->public_token);
    }
}
