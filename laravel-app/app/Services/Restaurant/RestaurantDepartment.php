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
        $seen = [];
        $guard = 0;
        while ($categoryId && $guard < 12) {
            $guard++;
            $id = (int) $categoryId;
            if (isset($seen[$id])) {
                break;
            }
            $seen[$id] = true;
            $category = Category::find($id);
            if (! $category) {
                break;
            }
            $department = self::normalize($category->department);
            if ($department) {
                return $department;
            }
            $categoryId = $category->parent_id;
        }

        return null;
    }
}
