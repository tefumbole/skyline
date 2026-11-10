<?php

namespace App\Http\Controllers;

use App\Asset;
use App\AssetExpense;
use App\ExpenseCategory;
use Illuminate\Http\Request;
use App\Expense;
use App\Account;
use App\CashRegister;
use App\Support\ExpenseBoard;
use App\Warehouse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Auth;
use DB;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        if (! ExpenseBoard::canView(Auth::user())) {
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $query = Expense::with('expenseCategory')->orderBy('created_at', 'desc');
        if ($request->filled('category')) {
            $query->where('expense_category_id', (int) $request->category);
        }
        if ($request->filled('event')) {
            $query->where('event_id', $request->event);
        }
        if ($request->filled('quotation')) {
            $query->where('quotation_id', (int) $request->quotation);
        }
        $expenses = $query->paginate(30)->appends($request->query());
        $spent = Expense::select('expense_category_id', DB::raw('SUM(amount) as spent'))
            ->groupBy('expense_category_id')
            ->pluck('spent', 'expense_category_id');

        return view('expense.index', [
            'expenses' => $expenses,
            'categories' => ExpenseCategory::where('is_active', true)->orderBy('name')->get(),
            'spent' => $spent,
            'canAdd' => ExpenseBoard::canAdd(Auth::user()),
            'canEdit' => ExpenseBoard::canEdit(Auth::user()),
        ]);
    }

    public function linkOptions(Request $request)
    {
        if (! ExpenseBoard::canView(Auth::user())) {
            return response()->json(['payments' => [], 'engineers' => []], 403);
        }

        return response()->json([
            'payments' => ExpenseBoard::paymentsFor($request->event_id, $request->quotation_id),
            'engineers' => ExpenseBoard::engineersFor($request->event_id, $request->quotation_id),
        ]);
    }

    private function role(){
        return Role::find(Auth::user()->role_id);
    }

    public function asset()
    {
        $permissions = Role::findByName($this->role()->name)->permissions;
        foreach ($permissions as $permission)
            $all_permission[] = $permission->name;

        $lims_expense_all = AssetExpense::orderBy('id', 'desc')->where('type', 'expense')->get();

        return view('expense.asset', compact('lims_expense_all', 'all_permission'));
    }

    public function assetActivity()
    {
            $permissions = Role::findByName($this->role()->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;

        $lims_expense_all = AssetExpense::orderBy('id', 'desc')->where('type', 'activity')->get();

        return view('fixed_asset.asset.activity', compact('lims_expense_all', 'all_permission'));
    }
    public function assetActivityRepair()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('fixed_assets')) {
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
        }
        $lims_expense_all = AssetExpense::orderBy('id', 'desc')->where('type', 'activity')->where('activity_type', 'Repair')->get();

        return view('fixed_asset.asset.activity_repair', compact('lims_expense_all', 'all_permission'));
    }

    public function assetStore(Request $request)
    {
        $data = $request->all();
        $data['reference_no'] = 'er-' . date("Ymd") . '-'. date("his");
        $data['user_id'] = Auth::id();
        if($data['type'] == 'activity' && $data['activity_type'] == 'milage' && $data['total_km'] == null) {
            $data['total_km'] = $data['end_km'] - $data['start_km'];
        }
        AssetExpense::create($data);
        return back()->with('message', 'Data inserted successfully');
    }

    public function store(Request $request)
    {
        if (! ExpenseBoard::canAdd(Auth::user())) {
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $data = $this->expensePayload($request);
        $data['reference_no'] = 'er-' . date("Ymd") . '-'. date("his");
        $data['user_id'] = Auth::id();
        $cash_register_data = CashRegister::where([
            ['user_id', $data['user_id']],
            ['warehouse_id', $data['warehouse_id']],
            ['status', true]
        ])->first();
        if($cash_register_data)
            $data['cash_register_id'] = $cash_register_data->id;
        $expense = Expense::create($data);
        $this->applyDate($expense, $request);
        if ($request->exists('payment_ids')) {
            ExpenseBoard::syncPayments($expense->id, (array) $request->input('payment_ids', []));
        }
        return redirect()->route('expenses.index')->with('message', 'Expense saved.');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if ($role->hasPermissionTo('expenses-edit')) {
            $lims_expense_data = Expense::find($id);
            return $lims_expense_data;
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function editAsset($id)
    {
        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if ($role->hasPermissionTo('fixed_assets')) {
            $lims_expense_data = AssetExpense::find($id);
            if($lims_expense_data['asset_id'] != null) {
                $lims_expense_data['asset_name'] = Asset::find($lims_expense_data['asset_id'])->name;
            }

            return $lims_expense_data;
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function showAsset($id)
    {
        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if ($role->hasPermissionTo('fixed_assets')) {
            $lims_expense_data = AssetExpense::find($id)->toArray();
            $lims_expense_data['asset_name'] = '';
            $lims_expense_data['account_name'] = '';
            $lims_expense_data['category_name'] = '';

            if($lims_expense_data['asset_id'] != null) {
                $lims_expense_data['asset_name'] = Asset::find($lims_expense_data['asset_id'])->name;
            }
            if($lims_expense_data['account_id'] != null) {
                $account = Account::find($lims_expense_data['account_id']);
                $lims_expense_data['account_name'] = $account->name . '|' . $account->account_no;
            }
            if($lims_expense_data['expense_category_id'] != null) {
                $lims_expense_data['category_name'] = ExpenseCategory::find($lims_expense_data['expense_category_id'])->name;
            }

            return $lims_expense_data;
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function update(Request $request, $id)
    {
        if (! ExpenseBoard::canEdit(Auth::user())) {
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
        $lims_expense_data = Expense::find($request->input('expense_id', $id));
        if (! $lims_expense_data) {
            return redirect()->route('expenses.index')->with('not_permitted', 'That expense was not found.');
        }
        $lims_expense_data->update($this->expensePayload($request, $lims_expense_data));
        $this->applyDate($lims_expense_data, $request);
        if ($request->exists('payment_ids')) {
            ExpenseBoard::syncPayments($lims_expense_data->id, (array) $request->input('payment_ids', []));
        }
        return redirect()->route('expenses.index')->with('message', 'Expense updated.');
    }

    protected function expensePayload(Request $request, Expense $existing = null)
    {
        $warehouseId = (int) $request->input('warehouse_id');
        if ($warehouseId < 1 && $existing) {
            $warehouseId = (int) $existing->warehouse_id;
        }
        if ($warehouseId < 1) {
            $warehouseId = (int) Auth::user()->warehouse_id;
        }
        if ($warehouseId < 1) {
            $warehouseId = (int) Warehouse::where('is_active', true)->value('id');
        }
        $data = [
            'expense_category_id' => (int) $request->input('expense_category_id'),
            'warehouse_id' => $warehouseId,
            'amount' => $request->input('amount'),
            'note' => $request->input('note'),
        ];
        if ($request->filled('account_id')) {
            $data['account_id'] = (int) $request->input('account_id');
        }
        if ($request->exists('event_id')) {
            $eventId = $request->input('event_id') ?: null;
            $quotationId = $request->filled('quotation_id') ? (int) $request->input('quotation_id') : null;
            if ($eventId) {
                $quotationId = null;
            }
            $data['event_id'] = $eventId;
            $data['quotation_id'] = $quotationId;
        }
        if ($request->filled('category_id')) {
            $data['category_id'] = (int) $request->input('category_id');
        }

        return $data;
    }

    protected function applyDate(Expense $expense, Request $request)
    {
        $date = (string) $request->input('spent_on');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return;
        }
        $expense->created_at = $date.' 12:00:00';
        $expense->save();
    }

    public function updateAsset(Request $request, $id)
    {
        $data = $request->all();
        if(!isset($data['approved'])){
            $data['approved'] = null;
        }
        $lims_expense_data = AssetExpense::find($data['id']);
        $lims_expense_data->update($data);
        return back()->with('message', 'Data updated successfully');
    }

    public function deleteBySelection(Request $request)
    {
        $expense_id = $request['expenseIdArray'];
        foreach ($expense_id as $id) {
            $lims_expense_data = Expense::find($id);
            $lims_expense_data->delete();
        }
        return 'Expense deleted successfully!';
    }

    public function destroy($id)
    {
        $lims_expense_data = Expense::find($id);
        $lims_expense_data->delete();
        return back()->with('not_permitted', 'Data deleted successfully');
    }

    public function destroyAsset($id)
    {
        $lims_expense_data = AssetExpense::find($id);
        $lims_expense_data->delete();
        return back()->with('not_permitted', 'Data deleted successfully');
    }
}
