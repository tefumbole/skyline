<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CothSection extends Model
{
    protected $fillable = ['code', 'name', 'sort', 'is_active'];

    public function tables()
    {
        return $this->hasMany(CothTable::class);
    }
}
