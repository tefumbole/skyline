<?php

namespace App\Support;

use App\Category;
use App\Product;
use Illuminate\Support\Facades\Schema;

class ServiceCatalog
{
    /** Name keyword => lucide icon, used when a category has no photo. */
    const CATEGORY_ICONS = [
        'laundry' => 'washing-machine', 'iron' => 'shirt', 'dry clean' => 'sparkles',
        'coffee' => 'coffee', 'espresso' => 'coffee', 'iced' => 'glass-water', 'tea' => 'cup-soda',
        'juice' => 'citrus', 'smoothie' => 'milk', 'food' => 'utensils', 'breakfast' => 'egg-fried',
        'pizza' => 'pizza', 'burger' => 'sandwich', 'dessert' => 'cake-slice', 'pastr' => 'croissant',
        'beer' => 'beer', 'wine' => 'wine', 'cocktail' => 'martini', 'mocktail' => 'martini',
        'spirit' => 'martini', 'whisk' => 'martini', 'gin' => 'martini', 'vodka' => 'martini',
        'cognac' => 'martini', 'aperitif' => 'martini', 'bar' => 'wine', 'tango' => 'wine',
        'draft' => 'beer', 'guinness' => 'beer', 'rtd' => 'beer', 'energy' => 'battery-charging',
        'snack' => 'cookie', 'drink' => 'cup-soda', 'water' => 'glass-water', 'dairy' => 'milk',
        'bakery' => 'croissant', 'fruit' => 'apple', 'vegetable' => 'carrot', 'complement' => 'utensils',
        'rice' => 'utensils', 'fufu' => 'utensils', 'chicken' => 'drumstick', 'fish' => 'fish',
    ];

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
            $isReservation = strcasecmp(trim($parent->name), 'Chill on the Hill') === 0;
            if (! $hasChildren && ! $hasProducts && ! $isReservation) {
                continue; // skip empty parent categories
            }
            $out[$parent->id] = [
                'label' => $parent->name,
                'blurb' => $isReservation
                    ? 'Reserve one table for one night, 24 December 2026 to 2 January 2027.'
                    : self::blurbFor($parent->name),
                'icon' => $isReservation ? 'calendar' : self::iconFor($parent->name),
                'reservation' => $isReservation,
                'url' => $isReservation ? url('/chill-on-the-hill') : null,
            ];
        }

        $first = [];
        $rest = [];
        foreach ($out as $id => $svc) {
            if (! empty($svc['reservation'])) {
                $first[$id] = $svc;
            } else {
                $rest[$id] = $svc;
            }
        }

        return $first + $rest;
    }

    public static function exists($key)
    {
        return isset(self::services()[$key]);
    }

    /**
     * Sub-categories of the given parent as display nodes (id, name, icon,
     * image, count). The parent itself leads when it holds products directly.
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

        $nodes = collect();
        if (Schema::hasTable('products') && Product::where('is_active', true)->where('category_id', $parent->id)->exists()) {
            $nodes->push(self::node($parent));
        }
        foreach (Category::where('parent_id', $parent->id)->orderBy('name')->get() as $child) {
            $nodes->push(self::node($child));
        }

        return $nodes->values();
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
            $ids = array_merge([(int) $categoryId], self::descendantIds((int) $categoryId));
        } else {
            $ids = Category::where('parent_id', $parent->id)->pluck('id')->all();
            $ids[] = $parent->id;
        }

        return Product::with('category')->where('is_active', true)->whereIn('category_id', $ids)->orderBy('name')->get();
    }

    /** The parent category's own photo, for the card on the services page. */
    public static function heroUrl($key)
    {
        $parent = Category::whereNull('parent_id')->find($key);
        if ($parent) {
            $own = self::categoryImage($parent);
            if ($own) {
                return $own;
            }
        }
        foreach (self::categories($key) as $node) {
            if (! empty($node['image'])) {
                return $node['image'];
            }
        }

        return null;
    }

    /** Product photo (or null to fall back to an icon). */
    public static function productImage($product)
    {
        return PosProductImage::photoUrl($product->image ?? null);
    }

    public static function categoryIcon($name)
    {
        $name = strtolower((string) $name);
        foreach (self::CATEGORY_ICONS as $word => $icon) {
            if (strpos($name, $word) !== false) {
                return $icon;
            }
        }

        return 'tag';
    }

    public static function categoryImage($category)
    {
        $filename = trim((string) ($category->image ?? ''));
        if ($filename === '' || strtolower($filename) === 'null' || $filename === 'zummXD2dvAtI.png') {
            return null;
        }
        $path = public_path('images/category/'.PosProductImage::firstName($filename));

        return is_file($path) && filesize($path) > 0 ? PosProductImage::catalogUrl('category', $filename) : null;
    }

    protected static function node($category)
    {
        return [
            'id' => (int) $category->id,
            'name' => $category->name,
            'icon' => self::categoryIcon($category->name),
            'image' => self::categoryImage($category) ?: self::sampleProductImage($category->id),
            'count' => self::productCount($category->id),
        ];
    }

    protected static function sampleProductImage($categoryId)
    {
        if (! Schema::hasTable('products')) {
            return null;
        }
        $ids = array_merge([(int) $categoryId], self::descendantIds((int) $categoryId));
        $products = Product::where('is_active', true)
            ->whereIn('category_id', $ids)
            ->whereNotNull('image')
            ->orderBy('name')
            ->limit(8)
            ->get(['image']);
        foreach ($products as $product) {
            $url = self::productImage($product);
            if ($url) {
                return $url;
            }
        }

        return null;
    }

    protected static function productCount($categoryId)
    {
        if (! Schema::hasTable('products')) {
            return 0;
        }
        $ids = array_merge([(int) $categoryId], self::descendantIds((int) $categoryId));

        return Product::where('is_active', true)->whereIn('category_id', $ids)->count();
    }

    protected static function descendantIds($categoryId)
    {
        static $childrenMap = null;
        if ($childrenMap === null) {
            $childrenMap = [];
            if (Schema::hasTable('categories')) {
                foreach (Category::get(['id', 'parent_id']) as $row) {
                    $pid = (int) ($row->parent_id ?: 0);
                    if ($pid > 0 && $pid !== (int) $row->id) {
                        $childrenMap[$pid][] = (int) $row->id;
                    }
                }
            }
        }

        $ids = [];
        $queue = [(int) $categoryId];
        while ($queue) {
            $parent = array_shift($queue);
            foreach ($childrenMap[$parent] ?? [] as $kid) {
                $ids[] = (int) $kid;
                $queue[] = (int) $kid;
            }
        }

        return $ids;
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
