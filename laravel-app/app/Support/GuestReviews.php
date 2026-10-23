<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GuestReviews
{
    public static function summary()
    {
        $empty = ['count' => 0, 'average' => null, 'rows' => collect()];
        if (! Schema::hasTable('guest_reviews')) {
            return $empty;
        }

        $count = (int) DB::table('guest_reviews')->count();
        $average = null;
        if ($count > 0) {
            $row = DB::table('guest_reviews')
                ->selectRaw('AVG((cleanliness + on_time + service + food_quality) / 4) as score')
                ->first();
            if ($row && $row->score !== null) {
                $average = round((float) $row->score, 1);
            }
        }

        $rows = DB::table('guest_reviews')->orderByDesc('id')->limit(40)->get()->map(function ($review) {
            $review->score = round(((int) $review->cleanliness + (int) $review->on_time + (int) $review->service + (int) $review->food_quality) / 4, 1);

            return $review;
        });

        return ['count' => $count, 'average' => $average, 'rows' => $rows];
    }
}
