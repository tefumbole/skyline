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
            $service = self::service($category);
            $name = strtolower(trim((string) $category->name));
            if ($service === 'coffee' || $name === 'coffee' || $name === 'coffee & tea') {
                return ['coffee', 'drinks'];
            }
            if ($service === 'restaurant' || $name === 'food' || $name === 'restaurant') {
                return ['restaurant', 'food'];
            }
            if ($name === 'drinks' || $name === 'bar') {
                return ['bar', 'drinks'];
            }
        }
        foreach ($chain as $category) {
            $department = self::normalize($category->department);
            if ($department === 'drinks') {
                return ['bar', 'drinks'];
            }
            if ($department === 'food') {
                return ['restaurant', 'food'];
            }
        }

        return [null, null];
    }

    public static function label($lane)
    {
        $map = [
            'coffee' => 'Coffee & Tea',
            'restaurant' => 'Restaurant',
            'bar' => 'Bar',
            'online' => 'Online order',
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
