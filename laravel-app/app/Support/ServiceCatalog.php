<?php

namespace App\Support;

use App\Category;
use App\Product;
use Illuminate\Support\Facades\Schema;

class ServiceCatalog
{
    /**
     * Services shown on the public page are the parent (top-level) categories
     * that actually have products or sub-categories. Keyed by category id.
     */
    public static function services()
    {
        if (! Schema::hasTable('categories')) {
            return [];
        }

        $out = [];
        $parents = Category::whereNull('parent_id')->orderBy('id')->get();
        foreach ($parents as $parent) {
            $childIds = Category::where('parent_id', $parent->id)->pluck('id');
            $hasChildren = $childIds->isNotEmpty();
            $hasProducts = false;
            if (Schema::hasTable('products')) {
                $ids = $childIds->all();
                $ids[] = $parent->id;
                $hasProducts = Product::where('is_active', true)->whereIn('category_id', $ids)->exists();
            }
            if (! $hasChildren && ! $hasProducts) {
                continue; // skip empty parent categories
            }
            $out[$parent->id] = [
                'label' => $parent->name,
                'blurb' => self::blurbFor($parent->name),
                'icon' => self::iconFor($parent->name),
            ];
        }

        return $out;
    }

    public static function exists($key)
    {
        return isset(self::services()[$key]);
    }

    /**
     * Sub-categories of the given parent category. The parent itself is
     * prepended when it holds products directly.
     */
    public static function categories($key)
    {
        if (! Schema::hasTable('categories')) {
            return collect();
        }
        $parent = Category::whereNull('parent_id')->find($key);
        if (! $parent) {
            return collect();
        }
        $rows = Category::where('parent_id', $parent->id)->orderBy('id')->get();
        if (Schema::hasTable('products') && Product::where('is_active', true)->where('category_id', $parent->id)->exists()) {
            $rows->prepend($parent);
        }

        return $rows;
    }

    public static function products($key, $categoryId = null)
    {
        if (! Schema::hasTable('products')) {
            return collect();
        }
        $parent = Category::whereNull('parent_id')->find($key);
        if (! $parent) {
            return collect();
        }
        if ($categoryId) {
            $ids = [(int) $categoryId];
        } else {
            $ids = Category::where('parent_id', $parent->id)->pluck('id')->all();
            $ids[] = $parent->id;
        }

        return Product::where('is_active', true)->whereIn('category_id', $ids)->orderBy('name')->get();
    }

    protected static function iconFor($name)
    {
        $n = strtolower((string) $name);
        if (strpos($n, 'restaurant') !== false || strpos($n, 'food') !== false || strpos($n, 'kitchen') !== false) {
            return 'utensils-crossed';
        }
        if (strpos($n, 'coffee') !== false || strpos($n, 'cafe') !== false) {
            return 'coffee';
        }
        if (strpos($n, 'bar') !== false) {
            return 'wine';
        }
        if (strpos($n, 'drink') !== false || strpos($n, 'juice') !== false) {
            return 'cup-soda';
        }
        if (strpos($n, 'hotel') !== false || strpos($n, 'room') !== false) {
            return 'bed-double';
        }

        return 'tag';
    }

    protected static function blurbFor($name)
    {
        return 'Browse '.$name.' and place one order.';
    }
}
