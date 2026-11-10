<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $fillable =[
        "code", "name", "is_active", "allocation_bucket_id", "wealth_subcategory_id", "budget_amount",
        "parent_id", "budget_months"
    ];

    public function expense() {
    	return $this->hasMany('App\Expense');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
