<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ManualTestReport extends Model
{
    protected $fillable = ['tester_name', 'phone', 'status', 'stage', 'payload'];
}
