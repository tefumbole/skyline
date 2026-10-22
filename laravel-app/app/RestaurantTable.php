<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    protected $fillable = ['name', 'description', 'capacity', 'is_active'];
}
