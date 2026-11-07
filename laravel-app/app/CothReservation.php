<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CothReservation extends Model
{
    protected $fillable = [
        'reference', 'public_token', 'coth_slot_id', 'coth_table_id', 'event_date',
        'nights', 'stay_discount_percent',
        'customer_id', 'customer_name', 'phone', 'email',
        'list_price', 'discount_amount', 'final_price', 'deposit_due',
        'extra_chairs_qty', 'extra_chair_rate', 'extra_chairs_amount', 'booking_total',
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

    public function nightCount()
    {
        $nights = (int) $this->nights;

        return $nights > 0 ? $nights : 1;
    }

    public function stayEnd()
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('coth_reservation_days')) {
            $last = \Illuminate\Support\Facades\DB::table('coth_reservation_days')
                ->where('coth_reservation_id', $this->id)
                ->max('event_date');
            if ($last) {
                return \Carbon\Carbon::parse($last);
            }
        }

        return $this->event_date->copy()->addDays($this->nightCount() - 1);
    }

    public function stayLabel()
    {
        $nights = $this->nightCount();
        if ($nights < 2) {
            return $this->event_date->format('l, j F Y').' · one day';
        }
        $percent = (int) $this->stay_discount_percent;
        $label = $this->event_date->format('j M').' – '.$this->stayEnd()->format('j M Y').' · '.$nights.' days';
        if ($percent > 0) {
            $label .= ' · '.$percent.'% off';
        }

        return $label;
    }
}
