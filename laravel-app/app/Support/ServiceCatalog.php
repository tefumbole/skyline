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
            'supermarket' => ['label' => 'Convenience store & supermarket', 'blurb' => 'Snacks, drinks, and everyday items from the shop.', 'icon' => 'shopping-basket', 'priority' => true],
            'restaurant' => ['label' => 'Bar & Restaurant', 'blurb' => 'Kitchen meals and the bar list, on one bill.', 'icon' => 'utensils-crossed'],
            'coffee' => ['label' => 'Coffee shop', 'blurb' => 'Coffee, tea, juices, and smoothies.', 'icon' => 'coffee'],
            'bar' => ['label' => 'Bar', 'blurb' => 'Beers, wines, spirits, and cocktails.', 'icon' => 'wine'],
            'laundry' => ['label' => 'Laundry', 'blurb' => 'Wash, iron, and dry cleaning.', 'icon' => 'shirt'],
            'fashion' => ['label' => 'Fashion store', 'blurb' => 'Clothes and accessories.', 'icon' => 'shopping-bag'],
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
        $named = [
            'supermarket' => ['%supermarket%', '%grocer%', '%snack%', '%convenience%'],
            'laundry' => ['%laundry%', '%dry clean%', '%iron%'],
            'fashion' => ['%fashion%', '%cloth%', '%shoe%', '%dress%'],
        ];
        if (isset($named[$key])) {
            return Category::where(function ($query) use ($named, $key) {
                foreach ($named[$key] as $like) {
                    $query->orWhere('name', 'like', $like);
                }
            })->orderBy('id')->get();
        }
        if ($key === 'restaurant') {
            $parent = Category::where('name', 'Restaurant')->whereNull('parent_id')->first();
            $rows = $parent ? Category::where('parent_id', $parent->id)->orderBy('id')->get() : collect();
            if ($parent && Schema::hasTable('products') && Product::where('is_active', true)->where('category_id', $parent->id)->exists()) {
                $rows->prepend($parent);
            }

            return $rows;
        }
        if ($key === 'coffee') {
            return Category::where(function ($query) {
                $query->where('name', 'like', '%Coffee%')
                    ->orWhere('name', 'like', '%Juice%')
                    ->orWhere('name', 'like', '%Smoothie%')
                    ->orWhere('name', 'like', '%Tea%');
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
