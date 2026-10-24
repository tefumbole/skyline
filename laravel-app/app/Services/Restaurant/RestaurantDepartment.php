<?php

namespace App\Services\Restaurant;

use App\Category;

class RestaurantDepartment
{
    public static function normalize($value)
    {
        $value = strtolower(trim((string) $value));
        if ($value === 'food' || $value === 'drinks') {
            return $value;
        }

        return null;
    }

    public static function forCategoryId($categoryId)
    {
        $pair = self::laneAndDepartment($categoryId);

        return $pair[1];
    }

    public static function laneForCategoryId($categoryId)
    {
        return self::laneAndDepartment($categoryId)[0];
    }

    /**
     * @return array{0:?string,1:?string} lane, department
     */
    public static function laneAndDepartment($categoryId)
    {
        $chain = self::chain($categoryId);
        if (! $chain) {
            return [null, null];
        }
        foreach ($chain as $category) {
            $service = self::service($category);
            $name = strtolower(trim((string) $category->name));
            if ($service === 'laundry' || $name === 'laundry') {
                return [null, null];
            }
        }
        foreach ($chain as $category) {
            if (self::isFood($category)) {
                return ['restaurant', 'food'];
            }
        }
        foreach ($chain as $category) {
            if (self::isDrink($category)) {
                return ['bar', 'drinks'];
            }
        }

        return [null, null];
    }

    protected static function isFood($category)
    {
        $name = strtolower(trim((string) $category->name));
        $service = self::service($category);
        if (self::normalize(isset($category->department) ? $category->department : null) === 'food') {
            return true;
        }
        if ($service === 'restaurant' || $service === 'food') {
            return true;
        }

        return in_array($name, ['food', 'restaurant', 'complements', 'complement', 'sides'], true);
    }

    protected static function isDrink($category)
    {
        $name = strtolower(trim((string) $category->name));
        $service = self::service($category);
        if (self::normalize(isset($category->department) ? $category->department : null) === 'drinks') {
            return true;
        }
        if (in_array($service, ['bar', 'coffee', 'drinks'], true)) {
            return true;
        }
        foreach (['drink', 'bar', 'coffee', 'tea', 'whisk', 'wine', 'cocktail', 'smoothie', 'mocktail', 'spirit', 'beer', 'juice', 'cognac', 'vodka', 'gin', 'aperitif', 'champagne', 'soda', 'liquor'] as $needle) {
            if (strpos($name, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    public static function label($lane)
    {
        $map = [
            'coffee' => 'Bar',
            'restaurant' => 'Restaurant',
            'bar' => 'Bar',
            'online' => 'Online orders',
            'food' => 'Restaurant',
            'drinks' => 'Bar',
        ];

        return isset($map[$lane]) ? $map[$lane] : ucfirst((string) $lane);
    }

    protected static function service($category)
    {
        if (! isset($category->service)) {
            return '';
        }

        return strtolower(trim((string) $category->service));
    }

    protected static function chain($categoryId)
    {
        $chain = [];
        $seen = [];
        $guard = 0;
        while ($categoryId && $guard < 12) {
            $guard++;
            $id = (int) $categoryId;
            if ($id < 1 || isset($seen[$id])) {
                break;
            }
            $seen[$id] = true;
            $category = Category::find($id);
            if (! $category) {
                break;
            }
            $chain[] = $category;
            $categoryId = $category->parent_id;
        }

        return $chain;
    }
}
