<?php

namespace App\Http\Controllers;

use App\Account;
use App\Customer;
use App\Deposit;
use App\PublicDonation;
use App\Sale;
use App\Services\AccountDepositService;
use App\Services\CampayPayoutService;
use App\Services\ClientNoticeService;
use App\Services\Cloud\CloudTenantContext;
use App\Services\MobileMoneyHolderService;
use App\Services\Restaurant\RestaurantSettlement;
use App\Services\StripeCheckoutService;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PaymentController extends Controller
{
    public function index()
    {
        $this->applyReceivedMomo();
        $sales = $this->awaitingSales();
        $bills = $this->clientBills($sales);
        $lims_account_list = Account::orderByDesc('id')->get();
        return view('payment.customer-data', compact('sales', 'bills', 'lims_account_list'));
    }


    public function AwaitingPayments($id)
    {
        $this->applyReceivedMomo();
        $sales = $this->awaitingSales($id);
        $bills = $this->clientBills($sales);
        $lims_account_list = Account::orderByDesc('id')->get();
        return view('payment.customer-data', compact('sales', 'bills', 'lims_account_list'));
    }

    public function clientBill($customer)
    {
        $customer = Customer::find($customer);
        if (! $customer || $this->isSharedCustomer($customer)) {
            return redirect()->route('payment.index')->with('not_permitted', 'Open that walk-in bill and print it on its own.');
        }
        $sales = $this->awaitingSales($customer->id)->sortBy(function ($sale) {
            return $sale->created_at ? $sale->created_at->format('Y-m-d H:i:s') : '';
        })->values();
        if ($sales->isEmpty()) {
            return redirect()->route('payment.index')->with('message', 'This client has nothing left to pay.');
        }
        $lines = \App\Product_Sale::with('product')->whereIn('sale_id', $sales->pluck('id')->all())->get()->groupBy('sale_id');

        return view('payment.client_bill', compact('customer', 'sales', 'lines'));
    }

    public function payClient(Request $request)
    {
        $requested = $this->requestedSaleIds($request);
        if (! $requested) {
            return redirect()->route('payment.index')->with('not_permitted', 'Choose the bill again.');
        }
        $sales = $this->openSalesForPayment($requested);
        if ($sales->isEmpty()) {
            return redirect()->route('payment.index')->with('not_permitted', 'Those bills are no longer open.');
        }
        $customerId = (int) $sales->first()->customer_id;
        foreach ($sales as $sale) {
            if ((int) $sale->customer_id !== $customerId) {
                return redirect()->route('payment.index')->with('not_permitted', 'Choose bills for one client.');
            }
        }
        $customer = $sales->first()->customer;
        if ($sales->count() > 1 && $this->isSharedCustomer($customer)) {
            return redirect()->route('payment.index')->with('not_permitted', 'Walk-in bills stay separate.');
        }
        $amount = round((float) $request->input('amount'), 2);
        $split = $this->splitShares($request, $amount);
        if (is_string($split)) {
            return redirect()->route('payment.index')->with('not_permitted', $split);
        }
        if (is_array($split)) {
            return $this->takeSplitPayment($customer, $sales, $split, $request);
        }
        $slices = $this->paymentSlices($sales, $amount);
        if ($slices === null) {
            return redirect()->route('payment.index')->with('not_permitted', 'Enter an amount up to the amount still due.');
        }
        $paidBy = (int) $request->input('paid_by_id');
        if ($paidBy === 8) {
            return $this->askClientMomo($customer, $slices, $amount, $request);
        }

        foreach ($slices as $saleId => $slice) {
            $sale = Sale::find($saleId);
            if ($sale) {
                $this->recordCashSlice($sale, $slice, $request->input('account_id'));
            }
        }

        return redirect()->route('payment.index')
            ->with('message', 'Payment created successfully')
            ->with('payment_receipt_url', $this->receiptLink($sales->pluck('id')->all()));
    }

    public function collected()
    {
        $this->assertCanReadPayments();
        $sales = Sale::with('customer')
            ->where('paid_amount', '>', 0)
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get();

        return view('payment.collected', compact('sales'));
    }

    public function receipt($token)
    {
        $this->assertCanReadPayments();
        $ids = \Illuminate\Support\Facades\Cache::get('payment-receipt:'.$token);
        if (! is_array($ids) || count($ids) < 1) {
            return redirect()->route('payment.collected')->with('not_permitted', 'That receipt has expired. Print it from Collected.');
        }
        $sales = Sale::with('customer')->whereIn('id', $ids)->orderBy('created_at')->get();
        if ($sales->isEmpty()) {
            return redirect()->route('payment.collected')->with('not_permitted', 'That receipt has expired. Print it from Collected.');
        }
        $customer = $sales->first()->customer ?: (object) ['name' => 'Client'];
        $lines = \App\Product_Sale::with('product')->whereIn('sale_id', $sales->pluck('id')->all())->get()->groupBy('sale_id');

        return view('payment.client_bill', compact('customer', 'sales', 'lines'));
    }

    public function clientMomoWait($token)
    {
        $pending = \Illuminate\Support\Facades\Cache::get('client-momo:'.$token);
        if (! is_array($pending)) {
            return redirect()->route('payment.index')->with('not_permitted', 'This Mobile Money request has expired. The bill was not marked paid.');
        }
        $sale = Sale::with('customer')->find(isset($pending['sale_id']) ? $pending['sale_id'] : 0);

        return view('payment.momo_wait', [
            'sale' => $sale,
            'amount' => $pending['amount'],
            'phone' => isset($pending['phone']) ? $pending['phone'] : '',
            'parts' => isset($pending['parts']) ? $pending['parts'] : [],
            'referenceLabel' => isset($pending['label']) ? $pending['label'] : optional(optional($sale)->customer)->name,
            'statusUrl' => route('payment.client-momo.status', ['token' => $token]),
            'backUrl' => route('payment.index'),
        ]);
    }

    public function clientMomoStatus($token)
    {
        $pending = \Illuminate\Support\Facades\Cache::get('client-momo:'.$token);
        if (! is_array($pending)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'This Mobile Money request has expired. The bill was not marked paid.',
            ]);
        }
        if (! empty($pending['parts']) && is_array($pending['parts'])) {
            return $this->splitMomoStatus($token, $pending);
        }
        if (empty($pending['slices']) || empty($pending['reference'])) {
            return response()->json([
                'status' => 'failed',
                'message' => 'This Mobile Money request has expired. The bill was not marked paid.',
            ]);
        }
        $reference = (string) $pending['reference'];
        if ($this->groupMomoRecorded($pending)) {
            \Illuminate\Support\Facades\Cache::forget('client-momo:'.$token);

            return $this->paidHome(array_keys($pending['slices']));
        }
        $campay = app(\App\Services\CampayCollectService::class);
        $pattern = '/^'.preg_quote($pending['external'], '/').'$/';
        $outcome = $campay->collectionOutcome($reference, $pending['amount'], $pending['phone'], $pattern);
        if ($outcome['state'] === 'paid' && ! empty($outcome['confirmed'])) {
            foreach ($pending['slices'] as $saleId => $slice) {
                $sale = Sale::find($saleId);
                if (! $sale || \App\Services\Restaurant\RestaurantPayments::hasReference($sale->id, $reference)) {
                    continue;
                }
                \App\Services\Restaurant\RestaurantPayments::recordGateway(
                    $sale,
                    $slice,
                    $reference,
                    isset($pending['user_id']) ? $pending['user_id'] : null
                );
            }
            \Illuminate\Support\Facades\Cache::forget('client-momo:'.$token);

            return $this->paidHome(array_keys($pending['slices']));
        }
        if ($outcome['state'] === 'failed') {
            \Illuminate\Support\Facades\Cache::forget('client-momo:'.$token);

            return response()->json([
                'status' => 'failed',
                'message' => $campay->payerFailure(isset($outcome['reason']) ? $outcome['reason'] : '', $pending['amount']),
            ]);
        }

        return response()->json(['status' => 'pending']);
    }

    protected function awaitingSales($customerId = null)
    {
        $query = Sale::with('customer')->orderByDesc('id');
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        $open = $query->where('payment_status', '!=', 4)
            ->whereRaw('COALESCE(grand_total, 0) - COALESCE(paid_amount, 0) > 0.009')
            ->get();
        $orders = app(\App\Services\Restaurant\RestaurantOrderService::class);
        $ids = [];
        foreach ($open as $sale) {
            $orders->releaseRejectedCharges($sale);
            $ids[] = (int) $sale->id;
        }
        if ($ids) {
            $open = Sale::with('customer')->whereIn('id', $ids)->orderByDesc('id')->get();
        }
        $open = $open->filter(function ($sale) {
            return max(0, (float) $sale->grand_total - (float) $sale->paid_amount) > 0.009;
        })->values();
        if (! Schema::hasTable('restaurant_orders') || ! Schema::hasTable('restaurant_order_items')) {
            return $open;
        }
        RestaurantSettlement::attach($open);

        return $open->filter(function ($sale) {
            $due = isset($sale->settlement['due']) ? (float) $sale->settlement['due'] : 0;

            return $due > 0.009;
        })->values();
    }

    protected function clientBills($sales)
    {
        $buckets = [];
        $loose = [];
        foreach ($sales as $sale) {
            $customer = $sale->customer;
            if (! $customer || $this->isSharedCustomer($customer)) {
                $loose[] = $this->makeBill(collect([$sale]));
                continue;
            }
            $id = (int) $customer->id;
            if (! isset($buckets[$id])) {
                $buckets[$id] = [];
            }
            $buckets[$id][] = $sale;
        }
        $bills = $loose;
        foreach ($buckets as $rows) {
            $bills[] = $this->makeBill(collect($rows));
        }
        usort($bills, function ($a, $b) {
            return strcmp($b['latest'], $a['latest']);
        });

        return $bills;
    }

    protected function makeBill($sales)
    {
        $sales = $sales->sortByDesc(function ($sale) {
            return $sale->created_at ? $sale->created_at->format('Y-m-d H:i:s') : '';
        })->values();
        $first = $sales->first();
        $customer = $first->customer;
        $total = 0;
        $paid = 0;
        $due = 0;
        $ids = [];
        $refs = [];
        $undelivered = [];
        $pending = true;
        $latest = '';
        foreach ($sales as $sale) {
            $settlement = isset($sale->settlement) ? $sale->settlement : null;
            $saleDue = $settlement ? (float) $settlement['due'] : max(0, (float) $sale->grand_total - (float) $sale->paid_amount);
            $total += (float) $sale->grand_total;
            $paid += (float) $sale->paid_amount;
            $due += $saleDue;
            $ids[] = (int) $sale->id;
            $refs[] = (string) $sale->reference_no;
            if ((int) $sale->payment_status !== 1 || (float) $sale->paid_amount > 0.009) {
                $pending = false;
            }
            if ($settlement && ! empty($settlement['undelivered'])) {
                foreach ($settlement['undelivered'] as $item) {
                    $undelivered[] = $item;
                }
            }
            $when = $sale->created_at ? $sale->created_at->format('Y-m-d H:i:s') : '';
            if ($when > $latest) {
                $latest = $when;
            }
        }
        if ($due <= 0.009) {
            $status = 'Paid';
        } elseif ($pending) {
            $status = 'Pending';
        } else {
            $status = 'Due';
        }

        return [
            'customer_id' => $customer ? (int) $customer->id : 0,
            'name' => $customer ? (string) $customer->name : 'Client',
            'unique_id' => $customer ? (string) $customer->unique_id : '',
            'phone' => $customer ? (string) $customer->phone_number : '',
            'shared' => ! $customer || $this->isSharedCustomer($customer),
            'sale_ids' => $ids,
            'references' => $refs,
            'orders' => count($ids),
            'total' => $total,
            'paid' => $paid,
            'due' => $due,
            'status' => $status,
            'undelivered' => $undelivered,
            'latest' => $latest,
            'latest_label' => $latest,
            'print_sale_id' => (int) $first->id,
        ];
    }

    protected function isSharedCustomer($customer)
    {
        $name = trim((string) $customer->name);

        return $name === '' || strcasecmp($name, 'Walk-in') === 0 || strcasecmp($name, 'WALKIN') === 0;
    }

    protected function requestedSaleIds(Request $request)
    {
        $ids = [];
        $chunks = array(
            $request->query('sale_ids'),
            $request->query('sale_id'),
            $request->request->get('sale_ids'),
            $request->request->get('sale_id'),
        );
        foreach ($chunks as $chunk) {
            $this->collectSaleIds($chunk, $ids);
        }

        return array_values($ids);
    }

    protected function collectSaleIds($value, array &$ids)
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->collectSaleIds($item, $ids);
            }

            return;
        }
        $text = trim((string) $value);
        if ($text === '' || strcasecmp($text, 'Array') === 0) {
            return;
        }
        foreach (explode(',', $text) as $part) {
            $part = trim($part);
            if ($part !== '' && ctype_digit($part)) {
                $id = (int) $part;
                if ($id > 0) {
                    $ids[$id] = $id;
                }
            }
        }
    }

    protected function openSalesForPayment(array $ids)
    {
        $sales = Sale::with('customer')->whereIn('id', $ids)->get();
        if ($sales->isEmpty()) {
            return $sales;
        }
        $orders = app(\App\Services\Restaurant\RestaurantOrderService::class);
        foreach ($sales as $sale) {
            $orders->releaseRejectedCharges($sale);
        }
        $sales = Sale::with('customer')->whereIn('id', $ids)->get()->filter(function ($sale) {
            if ((int) $sale->payment_status === 4) {
                return false;
            }

            return max(0, (float) $sale->grand_total - (float) $sale->paid_amount) > 0.009;
        })->values();
        if ($sales->isEmpty() || ! Schema::hasTable('restaurant_orders') || ! Schema::hasTable('restaurant_order_items')) {
            return $sales;
        }
        RestaurantSettlement::attach($sales);

        return $sales->filter(function ($sale) {
            $plain = max(0, (float) $sale->grand_total - (float) $sale->paid_amount);
            $due = isset($sale->settlement['due']) ? (float) $sale->settlement['due'] : $plain;
            if ($due <= 0.009) {
                $due = $plain;
            }

            return $due > 0.009;
        })->values();
    }

    protected function paymentSlices($sales, $amount)
    {
        if ($amount <= 0) {
            return null;
        }
        $ordered = $sales->sortBy(function ($sale) {
            return $sale->created_at ? $sale->created_at->format('Y-m-d H:i:s') : '';
        })->values();
        $totalDue = 0;
        $dues = [];
        foreach ($ordered as $sale) {
            $settlement = isset($sale->settlement) ? $sale->settlement : null;
            $due = $settlement ? (float) $settlement['due'] : max(0, (float) $sale->grand_total - (float) $sale->paid_amount);
            $dues[(int) $sale->id] = $due;
            $totalDue += $due;
        }
        if ($amount > $totalDue + 1.01) {
            return null;
        }
        if ($amount > $totalDue) {
            $amount = $totalDue;
        }
        $left = $amount;
        $slices = [];
        foreach ($ordered as $sale) {
            $due = $dues[(int) $sale->id];
            if ($left <= 0.009 || $due <= 0.009) {
                continue;
            }
            $slice = min($left, $due);
            $slices[(int) $sale->id] = round($slice, 2);
            $left = round($left - $slice, 2);
        }
        if (count($slices) < 1) {
            return null;
        }

        return $slices;
    }

    protected function recordCashSlice(Sale $sale, $amount, $accountId)
    {
        $amount = round((float) $amount, 2);
        if ($amount <= 0) {
            return;
        }
        \App\Support\TableBoard::releaseToCashier($sale);
        $sale->paid_amount = (float) $sale->paid_amount + $amount;
        $balance = (float) $sale->grand_total - (float) $sale->paid_amount;
        if ($balance <= 0.009) {
            $sale->payment_status = 4;
        } elseif ((float) $sale->paid_amount > 0.009) {
            $sale->payment_status = 3;
        } else {
            $sale->payment_status = 2;
        }
        $sale->save();
        $register = \App\CashRegister::where([
            ['user_id', Auth::id()],
            ['warehouse_id', $sale->warehouse_id],
            ['status', true],
        ])->first();
        $payment = new \App\Payment();
        $payment->user_id = Auth::id();
        $payment->sale_id = $sale->id;
        if ($register) {
            $payment->cash_register_id = $register->id;
        }
        $payment->account_id = $accountId ?: null;
        $payment->payment_reference = 'spr-'.date('Ymd-His').'-'.$sale->id;
        $payment->amount = $amount;
        $payment->change = 0;
        $payment->paying_method = 'Cash';
        $payment->payment_note = 'Combined client bill';
        $payment->save();
    }

    protected function askClientMomo($customer, array $slices, $amount, Request $request)
    {
        $phone = trim((string) $request->input('momo_phone'));
        if ($phone === '' && $customer) {
            $phone = (string) $customer->phone_number;
        }
        if ($phone === '') {
            return redirect()->route('payment.index')->with('not_permitted', 'This client has no phone number, so they cannot confirm. The bill was not marked paid.');
        }
        $collect = app(\App\Services\CampayCollectService::class);
        $momo = $collect->momoNumber($phone);
        if (! $momo) {
            return redirect()->route('payment.index')->with('not_permitted', 'The client number is not an MTN or Orange number. The bill was not marked paid.');
        }
        $external = 'client-momo-'.(int) $customer->id.'-'.date('His');
        $body = $collect->collect($momo, (int) round($amount), $external, 'Skyline '.$customer->name);
        $reference = is_array($body) && ! empty($body['reference']) ? (string) $body['reference'] : '';
        $campayStatus = strtoupper((string) (is_array($body) && isset($body['status']) ? $body['status'] : ''));
        if ($reference === '' || $campayStatus === 'FAILED') {
            return redirect()->route('payment.index')->with('not_permitted', 'Mobile Money did not send a prompt. The bill was not marked paid.');
        }
        foreach (array_keys($slices) as $saleId) {
            $sale = Sale::find($saleId);
            if ($sale) {
                \App\Support\TableBoard::releaseToCashier($sale);
            }
        }
        $token = bin2hex(random_bytes(16));
        $count = count($slices);
        \Illuminate\Support\Facades\Cache::put('client-momo:'.$token, [
            'amount' => (int) round($amount),
            'slices' => $slices,
            'sale_id' => (int) array_keys($slices)[0],
            'user_id' => Auth::id(),
            'reference' => $reference,
            'phone' => $momo,
            'external' => $external,
            'label' => $customer->name.($count > 1 ? ' · '.$count.' orders' : ''),
        ], now()->addMinutes(30));
        \Illuminate\Support\Facades\Cache::put($external, ['token' => $token], now()->addMinutes(30));

        return redirect()->route('payment.client-momo', ['token' => $token]);
    }

    protected function splitShares(Request $request, $amount)
    {
        $phones = $request->input('split_phones', []);
        $amounts = $request->input('split_amounts', []);
        $methods = $request->input('split_methods', []);
        if (! is_array($phones) || ! is_array($amounts)) {
            return null;
        }
        $collect = app(\App\Services\CampayCollectService::class);
        $shares = [];
        $seen = [];
        $count = max(count($phones), count($amounts), is_array($methods) ? count($methods) : 0);
        for ($i = 0; $i < $count; $i++) {
            $phone = trim((string) (isset($phones[$i]) ? $phones[$i] : ''));
            $share = isset($amounts[$i]) ? (int) round((float) $amounts[$i]) : 0;
            $method = (is_array($methods) && isset($methods[$i]) && $methods[$i] === 'cash') ? 'cash' : 'momo';
            if ($phone === '' && $share <= 0) {
                continue;
            }
            if ($method === 'cash') {
                if ($share < 1) {
                    return 'Each cash share needs an amount.';
                }
                $shares[] = ['type' => 'cash', 'amount' => $share];
                continue;
            }
            $momo = $collect->momoNumber($phone);
            if (! $momo) {
                return 'Each Mobile Money share needs an MTN or Orange number.';
            }
            if ($share < 100) {
                return 'Each Mobile Money share must be at least 100 XAF.';
            }
            if (isset($seen[$momo])) {
                return 'Use a different Mobile Money number for each person.';
            }
            $seen[$momo] = true;
            $shares[] = ['type' => 'momo', 'phone' => $momo, 'amount' => $share];
        }
        if (count($shares) === 0) {
            return null;
        }
        if (count($shares) < 2) {
            return 'Split payment needs at least two shares.';
        }
        $sum = 0;
        foreach ($shares as $share) {
            $sum += $share['amount'];
        }
        if (abs($sum - (int) round($amount)) > 1) {
            return 'The shares must add up to the amount still due.';
        }

        return $shares;
    }

    protected function takeSplitPayment($customer, $sales, array $shares, Request $request)
    {
        $cash = 0;
        $momo = [];
        foreach ($shares as $share) {
            if (isset($share['type']) && $share['type'] === 'cash') {
                $cash += (int) $share['amount'];
            } else {
                $momo[] = $share;
            }
        }
        $saleIds = [];
        foreach ($sales as $sale) {
            $saleIds[] = (int) $sale->id;
        }
        if ($cash > 0) {
            $slices = $this->paymentSlices($sales, $cash);
            if ($slices === null) {
                return redirect()->route('payment.index')->with('not_permitted', 'The cash shares are more than the amount still due.');
            }
            foreach ($slices as $saleId => $slice) {
                $sale = Sale::find($saleId);
                if ($sale) {
                    $this->recordCashSlice($sale, $slice, $request->input('account_id'));
                }
            }
            $sales = $this->openSalesForPayment($saleIds);
        }
        if (count($momo) > 0) {
            if ($sales->isEmpty()) {
                return redirect()->route('payment.index')
                    ->with('message', 'Payment created successfully')
                    ->with('payment_receipt_url', $this->receiptLink($saleIds));
            }

            return $this->askSplitMomo($customer, $sales, $momo);
        }

        return redirect()->route('payment.index')
            ->with('message', 'Payment created successfully')
            ->with('payment_receipt_url', $this->receiptLink($saleIds));
    }

    protected function askSplitMomo($customer, $sales, array $shares)
    {
        $total = 0;
        foreach ($shares as $share) {
            $total += $share['amount'];
        }
        $due = 0;
        foreach ($sales as $sale) {
            $settlement = isset($sale->settlement) ? $sale->settlement : null;
            $due += $settlement ? (float) $settlement['due'] : max(0, (float) $sale->grand_total - (float) $sale->paid_amount);
        }
        if (abs($total - round($due)) > 1.01) {
            return redirect()->route('payment.index')->with('not_permitted', 'The shares must add up to the amount still due.');
        }
        if ($this->paymentSlices($sales, $total) === null) {
            return redirect()->route('payment.index')->with('not_permitted', 'Enter an amount up to the amount still due.');
        }
        $collect = app(\App\Services\CampayCollectService::class);
        $parts = [];
        $failed = 0;
        foreach ($shares as $index => $share) {
            $external = 'client-momo-'.(int) $customer->id.'-'.date('His').'-'.$index;
            $body = $collect->collect($share['phone'], $share['amount'], $external, 'Skyline '.$customer->name);
            $reference = is_array($body) && ! empty($body['reference']) ? (string) $body['reference'] : '';
            $campayStatus = strtoupper((string) (is_array($body) && isset($body['status']) ? $body['status'] : ''));
            $sent = $reference !== '' && $campayStatus !== 'FAILED';
            if (! $sent) {
                $failed++;
            }
            $parts[] = [
                'phone' => $share['phone'],
                'amount' => $share['amount'],
                'reference' => $reference,
                'external' => $external,
                'state' => $sent ? 'pending' : 'failed',
                'recorded' => false,
            ];
        }
        $sentCount = count($parts) - $failed;
        if ($sentCount < 1) {
            return redirect()->route('payment.index')->with('not_permitted', 'Mobile Money did not send a prompt. The bill was not marked paid.');
        }
        $saleIds = [];
        foreach ($sales as $sale) {
            $saleIds[] = (int) $sale->id;
            \App\Support\TableBoard::releaseToCashier($sale);
        }
        $token = bin2hex(random_bytes(16));
        \Illuminate\Support\Facades\Cache::put('client-momo:'.$token, [
            'parts' => $parts,
            'sale_ids' => $saleIds,
            'sale_id' => $saleIds[0],
            'amount' => $total,
            'user_id' => Auth::id(),
            'label' => $customer->name.' · '.count($parts).' numbers',
        ], now()->addMinutes(30));
        foreach ($parts as $part) {
            if (! empty($part['external']) && $part['state'] !== 'failed') {
                \Illuminate\Support\Facades\Cache::put($part['external'], ['token' => $token], now()->addMinutes(30));
            }
        }

        return redirect()->route('payment.client-momo', ['token' => $token]);
    }

    protected function splitMomoStatus($token, array $pending)
    {
        $collect = app(\App\Services\CampayCollectService::class);
        $parts = $pending['parts'];
        $changed = false;
        foreach ($parts as $index => $part) {
            if (! empty($part['recorded']) || (isset($part['state']) && $part['state'] === 'failed')) {
                continue;
            }
            if (empty($part['reference'])) {
                $parts[$index]['state'] = 'failed';
                $changed = true;
                continue;
            }
            $pattern = '/^'.preg_quote($part['external'], '/').'$/';
            $outcome = $collect->collectionOutcome($part['reference'], $part['amount'], $part['phone'], $pattern);
            if ($outcome['state'] === 'paid' && ! empty($outcome['confirmed'])) {
                $this->recordSplitPart($pending, $parts[$index]);
                $parts[$index]['state'] = 'paid';
                $parts[$index]['recorded'] = true;
                $changed = true;
            } elseif ($outcome['state'] === 'failed') {
                $parts[$index]['state'] = 'failed';
                $parts[$index]['reason'] = isset($outcome['reason']) ? $outcome['reason'] : '';
                $changed = true;
            }
        }
        $pending['parts'] = $parts;
        if ($changed) {
            \Illuminate\Support\Facades\Cache::put('client-momo:'.$token, $pending, now()->addMinutes(30));
        }
        $waiting = 0;
        $paid = 0;
        $failedAmount = 0;
        $states = [];
        foreach ($parts as $part) {
            $state = isset($part['state']) ? $part['state'] : 'pending';
            $states[] = ['state' => $state];
            if ($state === 'paid') {
                $paid += (int) $part['amount'];
            } elseif ($state === 'failed') {
                $failedAmount += (int) $part['amount'];
            } else {
                $waiting++;
            }
        }
        if ($waiting > 0) {
            return response()->json([
                'status' => 'pending',
                'message' => $paid > 0
                    ? number_format($paid).' XAF is approved. Still waiting for the other numbers.'
                    : 'Each person approves their own share. The bill is paid when every share is approved.',
                'parts' => $states,
            ]);
        }
        \Illuminate\Support\Facades\Cache::forget('client-momo:'.$token);
        if ($paid > 0) {
            $link = $this->receiptLink(isset($pending['sale_ids']) ? $pending['sale_ids'] : []);
            if ($link) {
                session()->flash('payment_receipt_url', $link);
            }
        }
        if ($failedAmount > 0) {
            $message = $paid > 0
                ? number_format($paid).' XAF was received. '.number_format($failedAmount).' XAF was not approved, so that part is still due.'
                : 'The Mobile Money shares were not approved. The bill was not marked paid.';

            return response()->json([
                'status' => 'failed',
                'message' => $message,
                'parts' => $states,
            ]);
        }

        return $this->paidHome(isset($pending['sale_ids']) ? $pending['sale_ids'] : []);
    }

    protected function recordSplitPart(array $pending, array $part)
    {
        $sales = Sale::whereIn('id', isset($pending['sale_ids']) ? $pending['sale_ids'] : [])->get();
        $slices = $this->paymentSlices($sales, $part['amount']);
        if ($slices === null) {
            return;
        }
        foreach ($slices as $saleId => $slice) {
            $sale = Sale::find($saleId);
            if (! $sale || \App\Services\Restaurant\RestaurantPayments::hasReference($sale->id, $part['reference'])) {
                continue;
            }
            \App\Services\Restaurant\RestaurantPayments::recordGateway(
                $sale,
                $slice,
                $part['reference'],
                isset($pending['user_id']) ? $pending['user_id'] : null
            );
        }
    }

    protected function groupMomoRecorded(array $pending)
    {
        $reference = (string) $pending['reference'];
        $seen = 0;
        foreach ($pending['slices'] as $saleId => $slice) {
            if (\App\Services\Restaurant\RestaurantPayments::hasReference((int) $saleId, $reference)) {
                $seen += (float) $slice;
            }
        }

        return $seen + 0.009 >= (float) $pending['amount'];
    }

    protected function applyReceivedMomo()
    {
        try {
            app(\App\Services\Restaurant\ReceivedCollects::class)->settle();
        } catch (\Throwable $e) {
        }
    }

    public function Desposit()
    {
        $deposits = Deposit::with(['customer'])->orderByDesc('id')->get();
        $paidAmounts = $this->paymentPaidAmounts($deposits);
        $accounts = Account::where('is_active', true)->orderBy('name')->get();
        $making = request()->get('new') === '1';
        $campayBalance = null;
        $this->syncAfterResponse();

        return view('payment.deposits', compact('deposits', 'accounts', 'making', 'campayBalance', 'paidAmounts'));
    }

    /**
     * Gross amount the client paid. A front-end request wins, then the Campay collect amount.
     *
     * @param  \Illuminate\Support\Collection  $deposits
     * @return array<int, float>
     */
    protected function paymentPaidAmounts($deposits)
    {
        $byRef = [];
        try {
            $items = Cache::get('skyline.campay.collects', []);
            if (! is_array($items)) {
                $items = [];
            }
            foreach ($items as $item) {
                $ref = trim((string) (isset($item['reference_uuid']) ? $item['reference_uuid'] : ''));
                if ($ref === '') {
                    continue;
                }
                $credit = (float) (isset($item['credit']) ? $item['credit'] : 0);
                $charge = (float) (isset($item['charge']) ? $item['charge'] : 0);
                $amount = (float) (isset($item['amount']) ? $item['amount'] : 0);
                $gross = $credit + max(0, $charge);
                if ($amount > $gross) {
                    $gross = $amount;
                }
                $byRef[$ref] = $gross;
            }
        } catch (\Throwable $e) {
            $byRef = [];
        }

        $donations = SchemaFacade::hasTable('public_donations')
            ? PublicDonation::where('status', 'paid')->orderByDesc('id')->limit(200)->get()
            : collect();
        $used = [];
        $paid = [];
        foreach ($deposits as $deposit) {
            $received = (float) $deposit->amount;
            $gross = $received;
            $ref = trim((string) $deposit->campay_reference);
            $donation = null;
            if ($ref !== '') {
                foreach ($donations as $row) {
                    if (isset($used[$row->id])) {
                        continue;
                    }
                    if (trim((string) $row->campay_reference) === $ref) {
                        $donation = $row;
                        break;
                    }
                }
            }
            if (! $donation) {
                $phone = preg_replace('/\D/', '', (string) ($deposit->mtn_number ?: optional($deposit->customer)->phone_number));
                $phone = strlen($phone) > 9 ? substr($phone, -9) : $phone;
                $best = null;
                $bestGap = 1201;
                $stamp = $deposit->created_at ? $deposit->created_at->getTimestamp() : 0;
                foreach ($donations as $row) {
                    if (isset($used[$row->id]) || (float) $row->amount + 0.5 < $received) {
                        continue;
                    }
                    $other = preg_replace('/\D/', '', (string) $row->phone);
                    $other = strlen($other) > 9 ? substr($other, -9) : $other;
                    if ($phone === '' || $other === '' || $phone !== $other) {
                        continue;
                    }
                    $gap = abs($stamp - ($row->created_at ? $row->created_at->getTimestamp() : 0));
                    if ($gap < $bestGap) {
                        $bestGap = $gap;
                        $best = $row;
                    }
                }
                if ($best && $bestGap <= 1200) {
                    $donation = $best;
                }
            }
            if ($donation) {
                $used[$donation->id] = true;
                $gross = (float) $donation->amount;
            } elseif ($ref !== '' && isset($byRef[$ref]) && $byRef[$ref] + 0.5 >= $received) {
                $gross = (float) $byRef[$ref];
            }
            $paid[(int) $deposit->id] = $gross;
        }

        return $paid;
    }

    public function depositDeleteSelection(Request $request)
    {
        $this->paymentsAdmin();
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('ids', [])))));
        if (count($ids) < 1) {
            return redirect()->route('deposit.index')->with('not_permitted', 'Select the deposits to delete.');
        }
        $deleted = Deposit::whereIn('id', $ids)->delete();

        return redirect()->route('deposit.index')->with('message', $deleted.' '.($deleted === 1 ? 'deposit was' : 'deposits were').' deleted.');
    }

    public function searchCustomers(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        if ($q === '') {
            return response()->json(['people' => []]);
        }
        $like = '%'.$q.'%';
        $rows = Customer::query()
            ->where('phone_number', '!=', '')
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)->orWhere('phone_number', 'like', $like);
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'phone_number']);
        $people = [];
        foreach ($rows as $row) {
            $people[] = [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'phone' => (string) $row->phone_number,
            ];
        }

        return response()->json(['people' => $people]);
    }

    public function phoneLookup(Request $request)
    {
        $campay = app(CampayPayoutService::class);
        $phone = $campay->momoNumber($request->get('phone'));
        if (! $phone) {
            return response()->json(['ok' => false, 'error' => 'Not an MTN or Orange Cameroon number.']);
        }
        $hit = app(MobileMoneyHolderService::class)->lookup($phone, true);
        $name = ($hit && ! empty($hit['name'])) ? $hit['name'] : '';
        if ($name === '') {
            return response()->json(['ok' => false, 'error' => 'Campay has no MTN or Orange name for this number.']);
        }

        return response()->json([
            'ok' => true,
            'phone' => $phone,
            'name' => $name,
            'operator' => $campay->operatorName($phone),
            'source' => ($hit && ! empty($hit['source'])) ? $hit['source'] : null,
        ]);
    }

    public function clientCheck(Request $request)
    {
        $customer = Customer::find((int) $request->get('customer_id'));
        if (! $customer) {
            return response()->json(['ok' => false]);
        }
        $campay = app(CampayPayoutService::class);
        $phone = $campay->momoNumber($customer->phone_number);
        $lookup = $phone ? app(MobileMoneyHolderService::class)->lookup($phone, true) : null;
        $momoName = is_array($lookup) && ! empty($lookup['name']) ? (string) $lookup['name'] : '';
        $last = $phone
            ? Deposit::where('mtn_number', $phone)->where('status', 2)->orderByDesc('id')->first()
            : null;
        $low = $last && strpos(strtoupper((string) $last->failure_reason), 'LOW_BALANCE') !== false;
        $recent = $low && $last->created_at && $last->created_at->gt(now()->subMinutes(20));

        return response()->json([
            'ok' => true,
            'phone' => $phone ? $phone : (string) $customer->phone_number,
            'operator' => $phone ? $campay->operatorName($phone) : '',
            'momo_name' => $momoName,
            'balance' => null,
            'balance_note' => 'Campay does not show the money on this phone until the client approves or refuses.',
            'warning' => $low ? 'The last request of '.$this->money($last->amount).' XAF failed because this number did not have enough money.' : '',
            'block_amount' => $recent ? (int) $last->amount : 0,
        ]);
    }

    public function depositStatus($id)
    {
        $deposit = Deposit::findOrFail($id);
        if ((int) $deposit->status === 0 && $deposit->campay_reference) {
            $this->settleOne($deposit);
            $deposit = $deposit->fresh();
        }
        $state = (int) $deposit->status === 1 ? 'paid' : ((int) $deposit->status === 2 ? 'failed' : 'pending');

        return response()->json([
            'status' => $state,
            'message' => $state === 'paid'
                ? 'The client approved '.$this->money($deposit->amount).' XAF.'
                : ($state === 'failed'
                    ? $this->failureText($deposit->failure_reason, $deposit->amount)
                    : 'Waiting for the client to approve on their phone.'),
        ]);
    }

    public function storeDeposit(Request $request, AccountDepositService $deposits)
    {
        $data = $request->validate([
            'account_id' => 'required|integer|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:1,3,4',
            'note' => 'nullable|string|max:1000',
            'customer_id' => 'nullable|integer',
            'client_phone' => 'nullable|string|max:30',
        ]);
        $account = Account::where('is_active', true)->findOrFail($data['account_id']);
        $method = (int) $data['payment_method'];
        $note = isset($data['note']) ? $data['note'] : null;
        $customer = null;
        if (! empty($data['customer_id'])) {
            $customer = Customer::find($data['customer_id']);
        }
        if (! $customer && ! empty($data['client_phone'])) {
            $customer = $this->customerFromPhone($data['client_phone']);
        }
        if ($method === 1) {
            $deposit = $deposits->record($account, $data['amount'], $method, $note, Auth::id());
            if ($customer) {
                $deposit->customer_id = $customer->id;
                $deposit->mtn_number = $customer->phone_number;
                $deposit->paid_notice = 1;
                $deposit->save();
                $this->tellClient($customer->phone_number, 'Skyline Hotel received your cash deposit of '.$this->money($deposit->amount).' XAF.');
            }

            return redirect()->route('deposit.index')->with('message', 'Deposit of '.$this->money($deposit->amount).' XAF saved to '.$account->name.'.');
        }

        if (! $customer) {
            return $this->depositProblem($request, 'Choose the client who is paying.');
        }
        $campay = app(CampayPayoutService::class);
        $phone = $campay->momoNumber($customer->phone_number);
        if ($method === 3 && ! $phone) {
            return $this->depositProblem($request, $customer->name.' does not have an MTN or Orange Cameroon number.');
        }
        if (! $phone) {
            $digits = preg_replace('/\D/', '', (string) $customer->phone_number);
            if ($digits === '') {
                return $this->depositProblem($request, $customer->name.' does not have a phone number.');
            }
            $phone = $digits;
        }
        $amount = (int) round((float) $data['amount']);
        if ($amount < 100 || abs((float) $data['amount'] - $amount) > 0.001) {
            return $this->depositProblem($request, 'Enter a whole amount of at least 100 XAF.');
        }
        if ($method === 3 && $phone) {
            $recentLow = Deposit::where('mtn_number', $phone)->where('status', 2)->where('created_at', '>=', now()->subMinutes(20))->orderByDesc('id')->first();
            if ($recentLow && strpos(strtoupper((string) $recentLow->failure_reason), 'LOW_BALANCE') !== false && $amount >= (int) $recentLow->amount) {
                return $this->depositProblem($request, 'This number could not pay '.$this->money($recentLow->amount).' XAF a few minutes ago. Use a smaller amount, or wait until the phone has enough money.');
            }
        }
        $reference = 'dep-'.date('YmdHis').'-'.substr(md5(uniqid('', true)), 0, 8);
        $deposit = Deposit::create([
            'amount' => $amount,
            'customer_id' => $customer->id,
            'user_id' => Auth::id(),
            'depositor_id' => Auth::id(),
            'account_id' => $account->id,
            'note' => $note !== null && $note !== '' ? $note : null,
            'payment_method' => $method,
            'payment_reference' => $reference,
            'mtn_number' => $phone,
            'status' => 0,
            'paid_notice' => 0,
        ]);
        $description = $note !== null && trim((string) $note) !== '' ? substr(trim((string) $note), 0, 80) : 'Deposit';
        if ($method === 3) {
            $body = $campay->collect($phone, $amount, $reference, $description);
            $campayStatus = strtoupper((string) (is_array($body) && isset($body['status']) ? $body['status'] : ''));
            if (! is_array($body) || empty($body['reference']) || $campayStatus === 'FAILED') {
                $reason = is_array($body) && ! empty($body['reason']) ? (string) $body['reason'] : $campayStatus;
                $text = $reason !== '' ? $this->failureText($reason, $amount) : $this->campayError($body, 'Campay could not ask the client to approve.');
                if ($reason !== '') {
                    $this->tellClient($phone, $text);
                }
                $deposit->status = 2;
                $deposit->failure_reason = $reason !== '' ? substr($reason, 0, 180) : null;
                $deposit->campay_reference = is_array($body) && ! empty($body['reference']) ? (string) $body['reference'] : null;
                $deposit->save();

                return $this->depositProblem($request, $text);
            }
            $deposit->campay_reference = (string) $body['reference'];
            $deposit->save();
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => true, 'id' => $deposit->id, 'status' => 'pending']);
            }

            return redirect()->route('deposit.index', ['new' => 1, 'wait' => $deposit->id]);
        }

        $back = route('deposit.index');
        $card = app(StripeCheckoutService::class)->checkout(
            'Deposit',
            $amount,
            $back.'?session_id={CHECKOUT_SESSION_ID}',
            $back,
            ['deposit' => (string) $deposit->id]
        );
        if (empty($card['ok'])) {
            $deposit->delete();

            return $this->depositProblem($request, isset($card['error']) ? $card['error'] : 'VISA could not be opened.');
        }
        $deposit->campay_reference = (string) $card['id'];
        $deposit->payment_link = (string) $card['url'];
        $deposit->save();
        $channel = $this->tellClient($phone, 'Open this link and enter your card details to pay '.$this->money($amount).' XAF: '.$card['url']);

        return redirect()->route('deposit.index')->with('message', $channel === 'none'
            ? 'The card link was created, but the client could not be notified.'
            : 'A card link was sent to '.$customer->name.'.');
    }

    public function depositLive()
    {
        $this->settleConfirmedMoney();
        $total = '';
        try {
            $cached = Cache::get('skyline.campay.app_balance');
            $total = is_string($cached) ? $cached : '';
        } catch (\Throwable $e) {
            $total = '';
        }
        $rows = [];
        if (SchemaFacade::hasTable('public_donations')) {
            foreach (PublicDonation::orderByDesc('id')->limit(100)->get(['id', 'status']) as $row) {
                $rows[] = [
                    'id' => (int) $row->id,
                    'status' => $row->status === 'paid' ? 'Paid' : ($row->status === 'failed' ? 'Not paid' : 'Pending'),
                    'tone' => $row->status === 'paid' ? 'ok' : ($row->status === 'failed' ? 'no' : 'wait'),
                ];
            }
        }

        return response()->json([
            'total' => $total,
            'balance' => $total,
            'pending' => collect($rows)->contains(function ($row) {
                return $row['status'] === 'Pending';
            }),
            'rows' => $rows,
        ]);
    }

    public function settleFromGateway(Deposit $deposit)
    {
        $this->settleOne($deposit);
    }

    public function settleConfirmedMoney()
    {
        if (! Cache::add('skyline.payments.sync', 1, 45)) {
            return;
        }
        try {
            $this->settleWaitingDeposits();
            $this->refreshFrontDeposits();
            $this->importConfirmedCollections();
            app(\App\Services\Restaurant\MomoBillSettlement::class)->settleNotedDeposits();
            $items = app(CampayPayoutService::class)->confirmedCollections(
                date('Y-m-d', strtotime('-14 days')),
                date('Y-m-d')
            );
            Cache::put('skyline.campay.collects', $items, 300);
            app(CampayPayoutService::class)->balance();
        } catch (\Throwable $e) {
        }
    }

    protected function syncAfterResponse()
    {
        app()->terminating(function () {
            $this->settleConfirmedMoney();
        });
    }

    public function importConfirmedCollections()
    {
        if (! SchemaFacade::hasTable('deposits') || ! SchemaFacade::hasColumn('deposits', 'campay_reference')) {
            return;
        }
        try {
            $items = app(CampayPayoutService::class)->confirmedCollections(date('Y-m-d', strtotime('-2 days')), date('Y-m-d'));
        } catch (\Throwable $e) {
            return;
        }
        $accountId = Account::where('is_active', true)->value('id');
        foreach ($items as $item) {
            $reference = trim((string) (isset($item['reference_uuid']) ? $item['reference_uuid'] : ''));
            if ($reference === '' || Deposit::where('campay_reference', $reference)->exists()) {
                continue;
            }
            $credit = (int) round((float) $item['credit']);
            if ($credit < 1) {
                continue;
            }
            $phone = isset($item['phone_number']) ? (string) $item['phone_number'] : '';
            $when = date('Y-m-d H:i:s');
            if (! empty($item['datetime'])) {
                $stamp = strtotime((string) $item['datetime']);
                if ($stamp) {
                    $when = date('Y-m-d H:i:s', $stamp);
                }
            }
            $note = trim((string) (isset($item['description']) ? $item['description'] : ''));
            if ($note === '') {
                $note = 'Campay confirmed';
            }
            $settled = app(\App\Services\Restaurant\MomoBillSettlement::class)->settleDescribedMoney($note, $reference, $credit, Auth::id() ?: 1);
            $customer = ($settled && $settled->customer) ? $settled->customer : null;
            if (! $customer) {
                try {
                    $customer = $this->customerFromPhone($phone);
                } catch (\Throwable $e) {
                    $customer = null;
                }
            }
            Deposit::create([
                'amount' => $credit,
                'customer_id' => $customer ? $customer->id : null,
                'user_id' => Auth::id() ?: 1,
                'account_id' => $accountId,
                'mtn_number' => $phone,
                'payment_method' => 3,
                'status' => 1,
                'note' => substr($note, 0, 180),
                'campay_reference' => $reference,
                'created_at' => $when,
                'updated_at' => $when,
            ]);
        }
    }

    public function refreshFrontDeposits()
    {
        if (! SchemaFacade::hasTable('public_donations')) {
            return;
        }
        $pending = PublicDonation::whereIn('status', ['pending', 'waiting'])->orderByDesc('id')->limit(5)->get();
        $gateway = app(PublicDonateController::class);
        foreach ($pending as $donation) {
            try {
                $gateway->applyGateway($donation);
            } catch (\Throwable $e) {
                \Log::warning('Front deposit status check failed', ['id' => $donation->id, 'error' => $e->getMessage()]);
            }
        }
    }

    protected function settleWaitingDeposits()
    {
        if (! SchemaFacade::hasColumn('deposits', 'campay_reference')) {
            return;
        }
        $waiting = Deposit::where('status', 0)->whereNotNull('campay_reference')->orderBy('id')->limit(8)->get();
        foreach ($waiting as $deposit) {
            $this->settleOne($deposit);
        }
    }

    protected function settleOne(Deposit $deposit)
    {
        if ((int) $deposit->status !== 0 || ! $deposit->campay_reference) {
            return;
        }
        if (strpos((string) $deposit->campay_reference, 'cs_') === 0) {
            if (app(StripeCheckoutService::class)->isPaid($deposit->campay_reference)) {
                $paid = app(AccountDepositService::class)->creditPending($deposit);
                if ($paid && (int) $paid->paid_notice !== 1) {
                    $paid->paid_notice = 1;
                    $paid->save();
                    $this->tellClient($paid->mtn_number, 'Skyline Hotel received your deposit of '.$this->money($paid->amount).' XAF.');
                }
            }

            return;
        }
        $body = app(CampayPayoutService::class)->transaction($deposit->campay_reference);
        if (! is_array($body)) {
            return;
        }
        $status = strtoupper((string) (isset($body['status']) ? $body['status'] : ''));
        if ($status === 'SUCCESSFUL') {
            $paid = app(AccountDepositService::class)->creditPending($deposit);
            if ($paid && (int) $paid->paid_notice !== 1) {
                $paid->paid_notice = 1;
                $paid->save();
                $this->tellClient($paid->mtn_number, 'Skyline Hotel received your deposit of '.$this->money($paid->amount).' XAF.');
            }
        } elseif ($status === 'FAILED') {
            $reason = isset($body['reason']) ? substr((string) $body['reason'], 0, 180) : 'FAILED';
            $updated = Deposit::where('id', $deposit->id)->where('status', 0)->update([
                'status' => 2,
                'failure_reason' => $reason,
            ]);
            if ($updated) {
                $this->tellClient($deposit->mtn_number, $this->failureText($reason, $deposit->amount));
            }
        }
    }

    protected function depositProblem(Request $request, $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => false, 'message' => $message], 422);
        }

        return back()->withInput()->with('not_permitted', $message);
    }

    protected function failureText($reason, $amount)
    {
        $reason = strtoupper((string) $reason);
        $money = $this->money($amount);
        if (strpos($reason, 'LOW_BALANCE') !== false || strpos($reason, 'INSUFFICIENT') !== false) {
            return 'Your Mobile Money payment of '.$money.' XAF failed because the number does not have enough money.';
        }
        if ($reason === '' || strpos($reason, 'CANCEL') !== false || strpos($reason, 'TIMEOUT') !== false || strpos($reason, 'EXPIRED') !== false || strpos($reason, 'NOT_APPROV') !== false || strpos($reason, 'DENIED') !== false || strpos($reason, 'REJECT') !== false) {
            return 'Your Mobile Money payment of '.$money.' XAF failed because it was not approved.';
        }

        return 'Your Mobile Money payment of '.$money.' XAF failed.';
    }

    protected function customerFromPhone($raw)
    {
        $campay = app(CampayPayoutService::class);
        $phone = $campay->momoNumber($raw);
        if (! $phone) {
            $digits = preg_replace('/\D/', '', (string) $raw);
            $phone = $digits !== '' ? $digits : null;
        }
        if (! $phone) {
            return null;
        }
        $tail = substr($phone, -9);
        $customer = Customer::where(function ($query) use ($phone, $tail) {
            $query->where('phone_number', $phone)->orWhere('phone_number', 'like', '%'.$tail);
        })->first();
        if ($customer) {
            return $customer;
        }
        $hit = app(MobileMoneyHolderService::class)->lookup($phone, true);
        $name = ($hit && ! empty($hit['name'])) ? $hit['name'] : $phone;
        $groupId = \App\CustomerGroup::query()->value('id');
        $payload = [
            'customer_group_id' => $groupId ? $groupId : 1,
            'name' => $name,
            'phone_number' => $phone,
            'address' => 'N/A',
            'city' => 'N/A',
            'is_active' => true,
        ];
        try {
            return Customer::create($payload);
        } catch (\Throwable $e) {
            $tenant = \App\Cloud\CloudTenant::query()->orderBy('id')->first();
            if (! $tenant) {
                return null;
            }
            $ctx = app(CloudTenantContext::class);
            $previous = $ctx->get();
            $ctx->set($tenant);
            try {
                return Customer::create($payload);
            } catch (\Throwable $again) {
                return null;
            } finally {
                $ctx->set($previous);
            }
        }
    }

    protected function tellClient($phone, $message)
    {
        return app(ClientNoticeService::class)->send($phone, $message);
    }

    protected function money($amount)
    {
        return number_format((float) $amount, 0, '.', ' ');
    }

    protected function campayError($body, $fallback)
    {
        if (! is_array($body)) {
            return $fallback;
        }
        foreach (['message', 'detail', 'error', 'reason'] as $key) {
            if (! empty($body[$key]) && is_string($body[$key])) {
                return substr($body[$key], 0, 250);
            }
        }

        return $fallback;
    }

    protected function paidHome(array $saleIds)
    {
        $link = $this->receiptLink($saleIds);
        if ($link) {
            session()->flash('payment_receipt_url', $link);
        }

        return response()->json([
            'status' => 'paid',
            'url' => $link ?: route('payment.index'),
            'print' => $link,
        ]);
    }

    public function applyClientMomoWebhook(Request $request, $reference, $external)
    {
        $pointer = \Illuminate\Support\Facades\Cache::get($external);
        if (! is_array($pointer) || empty($pointer['token'])) {
            return;
        }
        $token = (string) $pointer['token'];
        $pending = \Illuminate\Support\Facades\Cache::get('client-momo:'.$token);
        if (! is_array($pending)) {
            return;
        }
        $body = $request->all();
        if (empty($body['external_reference'])) {
            $body['external_reference'] = $external;
        }
        if (empty($body['currency'])) {
            $body['currency'] = 'XAF';
        }
        if (strtoupper(trim((string) (isset($body['status']) ? $body['status'] : ''))) !== 'SUCCESSFUL') {
            return;
        }
        $collect = app(\App\Services\CampayCollectService::class);
        $pattern = '/^'.preg_quote((string) $external, '/').'$/';
        if (! empty($pending['parts']) && is_array($pending['parts'])) {
            foreach ($pending['parts'] as $index => $part) {
                if ((string) $part['external'] !== (string) $external || ! empty($part['recorded'])) {
                    continue;
                }
                if (! $collect->collectionDebited($body, $part['amount'], $part['phone'], $pattern)) {
                    return;
                }
                $part['reference'] = $reference;
                $this->recordSplitPart($pending, $part);
                $pending['parts'][$index]['reference'] = $reference;
                $pending['parts'][$index]['state'] = 'paid';
                $pending['parts'][$index]['recorded'] = true;
                \Illuminate\Support\Facades\Cache::put('client-momo:'.$token, $pending, now()->addMinutes(30));

                return;
            }

            return;
        }
        if ((string) (isset($pending['external']) ? $pending['external'] : '') !== (string) $external) {
            return;
        }
        if (! $collect->collectionDebited($body, $pending['amount'], $pending['phone'], $pattern)) {
            return;
        }
        foreach ($pending['slices'] as $saleId => $slice) {
            $sale = Sale::find($saleId);
            if (! $sale || \App\Services\Restaurant\RestaurantPayments::hasReference($sale->id, $reference)) {
                continue;
            }
            \App\Services\Restaurant\RestaurantPayments::recordGateway(
                $sale,
                $slice,
                $reference,
                isset($pending['user_id']) ? $pending['user_id'] : null
            );
        }
        $pending['reference'] = $reference;
        \Illuminate\Support\Facades\Cache::put('client-momo:'.$token, $pending, now()->addMinutes(30));
    }

    protected function receiptLink(array $saleIds)
    {
        $ids = [];
        foreach ($saleIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        $ids = array_values($ids);
        if (count($ids) < 1) {
            return null;
        }
        if (count($ids) === 1) {
            return route('sale.invoice', $ids[0]).'?print=1';
        }
        $token = bin2hex(random_bytes(8));
        \Illuminate\Support\Facades\Cache::put('payment-receipt:'.$token, $ids, now()->addHours(12));

        return route('payment.receipt', ['token' => $token]).'?print=1';
    }

    protected function assertCanReadPayments()
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }
        if ($user->hasFullAdminAccess()) {
            return;
        }
        try {
            $role = \Spatie\Permission\Models\Role::find($user->role_id);
            if ($role && $role->hasPermissionTo('payments-index')) {
                return;
            }
        } catch (\Throwable $e) {
        }
        abort(403);
    }

    protected function paymentsAdmin()
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }
        if ($user->hasFullAdminAccess() || in_array((int) $user->role_id, [1, 2, 4, 8, 10], true)) {
            return;
        }
        abort(403);
    }
}
