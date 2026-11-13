<?php

namespace App\Http\Controllers;

use App\Account;
use App\RestaurantTable;
use App\Support\TableBoard;
use App\Support\TableOrderEditor;

class TableBoardController extends Controller
{
    public function index()
    {
        if ($deny = $this->deny()) {
            return $deny;
        }

        $open = [];
        foreach (TableBoard::rows() as $row) {
            $open[(int) $row['id']] = $row;
        }
        $tables = [];
        foreach (RestaurantTable::numbered() as $table) {
            $id = (int) $table->id;
            if (isset($open[$id])) {
                $tables[] = $open[$id];
                unset($open[$id]);
                continue;
            }
            $tables[] = [
                'id' => $id,
                'name' => $table->name,
                'bills' => 0,
                'due' => 0,
                'total' => 0,
                'status' => 'Clear',
                'status_class' => 'delivered',
            ];
        }
        foreach ($open as $row) {
            $tables[] = $row;
        }

        return view('restaurant.table_board', [
            'tables' => $tables,
        ]);
    }

    public function show($table)
    {
        if ($deny = $this->deny()) {
            return $deny;
        }
        $row = TableBoard::table($table);
        if (! $row) {
            $record = RestaurantTable::where('id', $table)->where('is_active', 1)->first();
            if (! $record) {
                return redirect()->route('tables.index')->with('not_permitted', 'That table has no open order.');
            }
            $row = [
                'id' => (int) $record->id,
                'name' => $record->name,
                'description' => (string) $record->description,
                'due' => 0,
                'status' => 'Clear',
                'status_class' => 'delivered',
                'bills' => [],
            ];
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
