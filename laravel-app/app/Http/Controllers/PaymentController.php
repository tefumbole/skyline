<?php

namespace App\Http\Controllers;

use App\Account;
use App\Deposit;
use App\RestaurantOrder;
use App\Sale;
use App\Services\AccountDepositService;
use App\Services\Restaurant\RestaurantSettlement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class PaymentController extends Controller
{
    public function index()
    {
        $sales = $this->awaitingSales();
        $lims_account_list = Account::orderByDesc('id')->get();
        return view('payment.customer-data', compact('sales', 'lims_account_list'));
    }


    public function AwaitingPayments($id)
    {
        $sales = $this->awaitingSales($id);
        $lims_account_list = Account::orderByDesc('id')->get();
        return view('payment.customer-data', compact('sales', 'lims_account_list'));
    }

    protected function awaitingSales($customerId = null)
    {
        $query = Sale::with('customer')->orderByDesc('id');
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        $open = (clone $query)->where('payment_status', '!=', 4)->get();
        if (! Schema::hasTable('restaurant_orders') || ! Schema::hasTable('restaurant_order_items')) {
            return $open;
        }
        $ids = RestaurantOrder::whereHas('items', function ($items) {
            $items->whereNotIn('status', ['delivered', 'rejected', 'cancelled']);
        })->pluck('sale_id');
        $pendingPaid = collect();
        if ($ids->isNotEmpty()) {
            $paid = Sale::with('customer')->where('payment_status', 4)->whereIn('id', $ids);
            if ($customerId) {
                $paid->where('customer_id', $customerId);
            }
            $pendingPaid = $paid->orderByDesc('id')->get();
        }
        $sales = $open->merge($pendingPaid)->unique('id')->sortByDesc('id')->values();
        RestaurantSettlement::attach($sales);

        return $sales;
    }

    public function Desposit() {
        $deposits = Deposit::with(['customer', 'customerGroup', 'user', 'depositor', 'account'])->orderByDesc('id')->get();
        $accounts = Account::where('is_active', true)->orderBy('name')->get();
        return view('payment.deposits', compact('deposits', 'accounts'));
    }

    public function storeDeposit(Request $request, AccountDepositService $deposits)
    {
        $data = $request->validate([
            'account_id' => 'required|integer|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:1,2,3',
            'note' => 'nullable|string|max:1000',
        ]);
        $account = Account::where('is_active', true)->findOrFail($data['account_id']);
        $deposits->record($account, $data['amount'], $data['payment_method'], isset($data['note']) ? $data['note'] : null, Auth::id());

        return redirect()->route('deposit.index')->with('message', 'Deposit of '.number_format((float) $data['amount'], 2).' saved to '.$account->name.'.');
    }
}
