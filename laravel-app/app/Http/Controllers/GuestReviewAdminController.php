<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GuestReviewAdminController extends Controller
{
    public function index()
    {
        if (! auth()->user()->hasFullAdminAccess() && (int) auth()->user()->role_id !== 2) {
            return redirect('/')->with('not_permitted', 'You are not allowed to open guest reviews.');
        }

        $rows = Schema::hasTable('guest_reviews')
            ? DB::table('guest_reviews')->orderByDesc('id')->limit(200)->get()
            : collect();

        return view('guest_reviews.index', compact('rows'));
    }
}
