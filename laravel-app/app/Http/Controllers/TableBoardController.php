<?php

namespace App\Http\Controllers;

use App\Account;
use App\Support\TableBoard;

class TableBoardController extends Controller
{
    public function index()
    {
        if ($deny = $this->deny()) {
            return $deny;
        }

        return view('restaurant.table_board', [
            'tables' => TableBoard::rows(),
        ]);
    }

    public function show($table)
    {
        if ($deny = $this->deny()) {
            return $deny;
        }
        $row = TableBoard::table($table);
        if (! $row) {
            return redirect()->route('tables.index')->with('not_permitted', 'That table has no bill to show.');
        }

        return view('restaurant.table_board_show', [
            'table' => $row,
            'canPay' => TableBoard::canPay(auth()->user()),
            'accounts' => Account::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    protected function deny()
    {
        if (! TableBoard::canView(auth()->user())) {
            return redirect('/')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }

        return null;
    }
}
