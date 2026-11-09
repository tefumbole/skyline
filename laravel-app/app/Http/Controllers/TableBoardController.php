<?php

namespace App\Http\Controllers;

use App\Account;
use App\Support\TableBoard;
use App\Support\TableOrderEditor;

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

    public function editOrder($table, $sale)
    {
        if ($deny = $this->deny()) {
            return $deny;
        }
        $editor = app(TableOrderEditor::class);
        $order = $editor->orderOnTable($table, $sale);
        if (! $order) {
            return redirect()->route('tables.show', $table)->with('not_permitted', 'That order is not on this table.');
        }

        return view('restaurant.table_order_edit', [
            'tableId' => (int) $table,
            'tableName' => $editor->tableName($table),
            'sale' => $order->sale,
            'lines' => $editor->lines($order),
            'products' => $editor->products(),
        ]);
    }

    public function updateOrder(\Illuminate\Http\Request $request, $table, $sale)
    {
        if ($deny = $this->deny()) {
            return $deny;
        }
        $editor = app(TableOrderEditor::class);
        $order = $editor->orderOnTable($table, $sale);
        if (! $order) {
            return redirect()->route('tables.show', $table)->with('not_permitted', 'That order is not on this table.');
        }
        try {
            $message = $editor->apply(
                $order,
                (array) $request->input('qty', []),
                $request->input('product_id'),
                $request->input('add_qty'),
                $request->user()
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('not_permitted', $e->getMessage());
        }

        return redirect()->route('tables.show', $table)->with('message', $message);
    }

    protected function deny()
    {
        if (! TableBoard::canView(auth()->user())) {
            return redirect('/')->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }

        return null;
    }
}
