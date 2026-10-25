<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    protected $fillable = ['name', 'description', 'capacity', 'is_active'];

    public static function numbered()
    {
        return static::query()->where('is_active', 1)->get()->sortBy(function ($table) {
            return preg_match('/(\d+)/', (string) $table->name, $match) ? (int) $match[1] : 100000;
        })->values();
    }
}
