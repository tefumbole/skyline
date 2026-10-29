<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ManualTestResult extends Model
{
    protected $fillable = ['user_id', 'item_key', 'result'];
}
