<?php

namespace App\Support;

use App\Category;
use App\Product;
use Illuminate\Support\Facades\Schema;

class ServiceCatalog
{
    public static function services()
    {
        return [
            'restaurant' => ['label' => 'Restaurant', 'blurb' => 'Meals from the kitchen, on one bill.'],
            'coffee' => ['label' => 'Coffee & Tea', 'blurb' => 'Coffee, tea, juices, and smoothies.'],
            'bar' => ['label' => 'Bar', 'blurb' => 'Beers, wines, spirits, and cocktails.'],
        ];
    }

    public static function exists($key)
    {
        return isset(self::services()[$key]);
    }

    public static function categories($key)
    {
        if (! Schema::hasTable('categories')) {
            return collect();
        }
        if ($key === 'restaurant') {
            $parent = Category::where('name', 'Restaurant')->whereNull('parent_id')->first();

            return $parent ? Category::where('parent_id', $parent->id)->orderBy('id')->get() : collect();
        }
        if ($key === 'coffee') {
            return Category::where(function ($query) {
                $query->where('name', 'like', '%Coffee%')
                    ->orWhere('name', 'like', '%Juice%')
                    ->orWhere('name', 'like', '%Smoothie%');
            })->orderBy('id')->get();
        }
        $parent = Category::where('name', 'Bar')->whereNull('parent_id')->first();
        if (! $parent) {
            return collect();
        }

        return Category::where('parent_id', $parent->id)
            ->where('name', 'not like', '%Coffee%')
            ->where('name', 'not like', '%Juice%')
            ->where('name', 'not like', '%Smoothie%')
            ->orderBy('id')
            ->get();
    }

    public static function products($key, $categoryId = null)
    {
        if (! Schema::hasTable('products')) {
            return collect();
        }
        $ids = self::categories($key)->pluck('id')->all();
        if ($key === 'restaurant') {
            $parent = Category::where('name', 'Restaurant')->whereNull('parent_id')->first();
            if ($parent) {
                $ids[] = $parent->id;
            }
        }
        if ($categoryId) {
            $ids = [(int) $categoryId];
        }
        if (! $ids) {
            return collect();
        }

        return Product::where('is_active', true)->whereIn('category_id', $ids)->orderBy('name')->get();
    }
}
