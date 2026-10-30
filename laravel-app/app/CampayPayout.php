<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CampayPayout extends Model
{
    protected $table = 'campay_payouts';

    protected $fillable = [
        'user_id', 'customer_id', 'request_id', 'person_name', 'phone', 'amount', 'currency', 'note', 'kind',
        'external_reference', 'campay_reference', 'status', 'operator', 'error', 'momo_name', 'momo_checked',
    ];
}
