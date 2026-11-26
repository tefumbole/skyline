<?php

namespace App\Support;

use App\Account;
use App\Services\Restaurant\RestaurantDepartment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChartOfAccounts
{
    public function catalog()
    {
        return array(
            array('class' => '1', 'group' => 'Equity and long-term debt', 'code' => '101', 'name' => 'Share capital', 'statement' => 'Balance sheet', 'normal' => 'Credit'),
            array('class' => '1', 'group' => 'Equity and long-term debt', 'code' => '106', 'name' => 'Reserves', 'statement' => 'Balance sheet', 'normal' => 'Credit'),
            array('class' => '1', 'group' => 'Equity and long-term debt', 'code' => '110', 'name' => 'Retained earnings', 'statement' => 'Balance sheet', 'normal' => 'Credit'),
            array('class' => '1', 'group' => 'Equity and long-term debt', 'code' => '130', 'name' => 'Net profit for the year', 'statement' => 'Balance sheet', 'normal' => 'Credit'),
            array('class' => '1', 'group' => 'Equity and long-term debt', 'code' => '139', 'name' => 'Net loss for the year', 'statement' => 'Balance sheet', 'normal' => 'Credit'),
            array('class' => '1', 'group' => 'Equity and long-term debt', 'code' => '162', 'name' => 'Bank loans', 'statement' => 'Balance sheet', 'normal' => 'Credit'),
            array('class' => '2', 'group' => 'Fixed assets', 'code' => '213', 'name' => 'Software (booking/PMS system)', 'statement' => 'Balance sheet', 'normal' => 'Debit'),
            array('class' => '2', 'group' => 'Fixed assets', 'code' => '221', 'name' => 'Land', 'statement' => 'Balance sheet', 'normal' => 'Debit'),
            array('class' => '2', 'group' => 'Fixed assets', 'code' => '231', 'name' => 'Hotel building', 'statement' => 'Balance sheet', 'normal' => 'Debit'),
            array('class' => '2', 'group' => 'Fixed assets', 'code' => '241', 'name' => 'Kitchen and laundry equipment', 'statement' => 'Balance sheet', 'normal' => 'Debit'),
            array('class' => '2', 'group' => 'Fixed assets', 'code' => '244', 'name' => 'Furniture and fittings (beds, TVs, AC units)', 'statement' => 'Balance sheet', 'normal' => 'Debit'),
            array('class' => '2', 'group' => 'Fixed assets', 'code' => '245', 'name' => 'Vehicles (shuttle)', 'statement' => 'Balance sheet', 'normal' => 'Debit'),
            array('class' => '2', 'group' => 'Fixed assets', 'code' => '283', 'name' => 'Accumulated depreciation, buildings', 'statement' => 'Balance sheet', 'normal' => 'Debit'),
            array('class' => '2', 'group' => 'Fixed assets', 'code' => '284', 'name' => 'Accumulated depreciation, equipment and furniture', 'statement' => 'Balance sheet', 'normal' => 'Debit'),
            array('class' => '3', 'group' => 'Inventory', 'code' => '311', 'name' => 'Food stock', 'statement' => 'Balance sheet', 'normal' => 'Debit'),
            array('class' => '3', 'group' => 'Inventory', 'code' => '312', 'name' => 'Beverage stock', 'statement' => 'Balance sheet', 'normal' => 'Debit'),
            array('class' => '3', 'group' => 'Inventory', 'code' => '331', 'name' => 'Consumables (cleaning supplies, toiletries, linen)', 'statement' => 'Balance sheet', 'normal' => 'Debit'),
            array('class' => '4', 'group' => 'Third parties', 'code' => '401', 'name' => 'Suppliers', 'statement' => 'Balance sheet', 'normal' => 'Mixed'),
            array('class' => '4', 'group' => 'Third parties', 'code' => '411', 'name' => 'Customers (corporate and travel agency accounts)', 'statement' => 'Balance sheet', 'normal' => 'Mixed'),
            array('class' => '4', 'group' => 'Third parties', 'code' => '4191', 'name' => 'Guest deposits and advance payments', 'statement' => 'Balance sheet', 'normal' => 'Mixed'),
            array('class' => '4', 'group' => 'Third parties', 'code' => '421', 'name' => 'Salaries payable', 'statement' => 'Balance sheet', 'normal' => 'Mixed'),
            array('class' => '4', 'group' => 'Third parties', 'code' => '431', 'name' => 'Social security payable (CNPS)', 'statement' => 'Balance sheet', 'normal' => 'Mixed'),
            array('class' => '4', 'group' => 'Third parties', 'code' => '4431', 'name' => 'VAT collected (19.25%)', 'statement' => 'Balance sheet', 'normal' => 'Mixed'),
            array('class' => '4', 'group' => 'Third parties', 'code' => '4452', 'name' => 'Recoverable VAT', 'statement' => 'Balance sheet', 'normal' => 'Mixed'),
            array('class' => '4', 'group' => 'Third parties', 'code' => '447', 'name' => 'Other taxes payable (withholdings, city taxes)', 'statement' => 'Balance sheet', 'normal' => 'Mixed'),
            array('class' => '4', 'group' => 'Third parties', 'code' => '461', 'name' => 'Owner drawings', 'statement' => 'Balance sheet', 'normal' => 'Debit'),
            array('class' => '4', 'group' => 'Third parties', 'code' => '476', 'name' => 'Prepaid expenses', 'statement' => 'Balance sheet', 'normal' => 'Mixed'),
            array('class' => '4', 'group' => 'Third parties', 'code' => '477', 'name' => 'Deferred income (advance bookings)', 'statement' => 'Balance sheet', 'normal' => 'Mixed'),
            array('class' => '5', 'group' => 'Cash and bank', 'code' => '521', 'name' => 'Bank accounts', 'statement' => 'Balance sheet', 'normal' => 'Debit'),
            array('class' => '5', 'group' => 'Cash and bank', 'code' => '5211', 'name' => 'Mobile money (MTN MoMo, Orange Money)', 'statement' => 'Balance sheet', 'normal' => 'Debit'),
            array('class' => '5', 'group' => 'Cash and bank', 'code' => '571', 'name' => 'Cash on hand (front desk, restaurant)', 'statement' => 'Balance sheet', 'normal' => 'Debit'),
            array('class' => '6', 'group' => 'Expenses', 'code' => '601', 'name' => 'Food and beverage purchases', 'statement' => 'Income statement', 'normal' => 'Debit'),
            array('class' => '6', 'group' => 'Expenses', 'code' => '604', 'name' => 'Cleaning and guest supplies', 'statement' => 'Income statement', 'normal' => 'Debit'),
            array('class' => '6', 'group' => 'Expenses', 'code' => '605', 'name' => 'Water, electricity, gas, fuel', 'statement' => 'Income statement', 'normal' => 'Debit'),
            array('class' => '6', 'group' => 'Expenses', 'code' => '622', 'name' => 'Rent and leasing', 'statement' => 'Income statement', 'normal' => 'Debit'),
            array('class' => '6', 'group' => 'Expenses', 'code' => '624', 'name' => 'Repairs and maintenance', 'statement' => 'Income statement', 'normal' => 'Debit'),
            array('class' => '6', 'group' => 'Expenses', 'code' => '625', 'name' => 'Insurance', 'statement' => 'Income statement', 'normal' => 'Debit'),
            array('class' => '6', 'group' => 'Expenses', 'code' => '627', 'name' => 'Advertising and marketing', 'statement' => 'Income statement', 'normal' => 'Debit'),
            array('class' => '6', 'group' => 'Expenses', 'code' => '628', 'name' => 'Telecom and internet', 'statement' => 'Income statement', 'normal' => 'Debit'),
            array('class' => '6', 'group' => 'Expenses', 'code' => '631', 'name' => 'Bank charges', 'statement' => 'Income statement', 'normal' => 'Debit'),
            array('class' => '6', 'group' => 'Expenses', 'code' => '632', 'name' => 'Commissions (booking platforms, agents)', 'statement' => 'Income statement', 'normal' => 'Debit'),
            array('class' => '6', 'group' => 'Expenses', 'code' => '641', 'name' => 'Taxes and duties (business license, property tax)', 'statement' => 'Income statement', 'normal' => 'Debit'),
            array('class' => '6', 'group' => 'Expenses', 'code' => '658', 'name' => 'Other operating expenses', 'statement' => 'Income statement', 'normal' => 'Debit'),
            array('class' => '6', 'group' => 'Expenses', 'code' => '661', 'name' => 'Salaries and wages', 'statement' => 'Income statement', 'normal' => 'Debit'),
            array('class' => '6', 'group' => 'Expenses', 'code' => '664', 'name' => 'Social charges (CNPS)', 'statement' => 'Income statement', 'normal' => 'Debit'),
            array('class' => '6', 'group' => 'Expenses', 'code' => '671', 'name' => 'Interest expense', 'statement' => 'Income statement', 'normal' => 'Debit'),
            array('class' => '6', 'group' => 'Expenses', 'code' => '681', 'name' => 'Depreciation expense', 'statement' => 'Income statement', 'normal' => 'Debit'),
            array('class' => '6', 'group' => 'Expenses', 'code' => '695', 'name' => 'Income tax', 'statement' => 'Income statement', 'normal' => 'Debit'),
            array('class' => '7', 'group' => 'Revenue', 'code' => '701', 'name' => 'Food and beverage sales (restaurant and bar)', 'statement' => 'Income statement', 'normal' => 'Credit'),
            array('class' => '7', 'group' => 'Revenue', 'code' => '7011', 'name' => 'Food sales', 'statement' => 'Income statement', 'normal' => 'Credit'),
            array('class' => '7', 'group' => 'Revenue', 'code' => '7012', 'name' => 'Drink sales', 'statement' => 'Income statement', 'normal' => 'Credit'),
            array('class' => '7', 'group' => 'Revenue', 'code' => '7061', 'name' => 'Room revenue', 'statement' => 'Income statement', 'normal' => 'Credit'),
            array('class' => '7', 'group' => 'Revenue', 'code' => '7062', 'name' => 'Conference and event revenue', 'statement' => 'Income statement', 'normal' => 'Credit'),
            array('class' => '7', 'group' => 'Revenue', 'code' => '7063', 'name' => 'Laundry and other guest services', 'statement' => 'Income statement', 'normal' => 'Credit'),
            array('class' => '7', 'group' => 'Revenue', 'code' => '7064', 'name' => 'Spa, parking, and shuttle services', 'statement' => 'Income statement', 'normal' => 'Credit'),
            array('class' => '7', 'group' => 'Revenue', 'code' => '707', 'name' => 'Other income (rentals, commissions received)', 'statement' => 'Income statement', 'normal' => 'Credit'),
            array('class' => '7', 'group' => 'Revenue', 'code' => '771', 'name' => 'Interest income', 'statement' => 'Income statement', 'normal' => 'Credit'),
            array('class' => '8', 'group' => 'Non-ordinary items', 'code' => '811', 'name' => 'Book value of assets sold', 'statement' => 'Income statement', 'normal' => 'Mixed'),
            array('class' => '8', 'group' => 'Non-ordinary items', 'code' => '821', 'name' => 'Proceeds from asset sales', 'statement' => 'Income statement', 'normal' => 'Mixed'),
        );
    }

    public function ensure()
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }
        foreach ($this->catalog() as $row) {
            $exists = Account::where('account_no', $row['code'])->exists();
            if ($exists) {
                continue;
            }
            $data = array(
                'account_no' => $row['code'],
                'name' => $row['name'],
                'initial_balance' => 0,
                'total_balance' => 0,
                'note' => 'Class '.$row['class'].' · '.$row['statement'].' · '.$row['normal'],
                'is_active' => 1,
                'is_default' => 0,
            );
            if (Schema::hasColumn('accounts', 'is_default_debit')) {
                $data['is_default_debit'] = 0;
            }
            if (Schema::hasColumn('accounts', 'department_id')) {
                $data['department_id'] = null;
            }
            Account::create($data);
        }
    }

    public function revenueAccountForSale($saleId)
    {
        $this->ensure();
        $split = $this->saleSplit($saleId);
        $code = '7011';
        if (isset($split['7012']) && $split['7012'] > (isset($split['7011']) ? $split['7011'] : 0)) {
            $code = '7012';
        }

        return Account::where('account_no', $code)->first();
    }

    public function expenseAccountFor($name, $note = '')
    {
        $this->ensure();
        $code = $this->expenseCode($name, $note);
        if ($code === null) {
            $code = '658';
        }

        return Account::where('account_no', $code)->first();
    }

    public function postSale($saleId)
    {
        $this->ensure();
        foreach ($this->saleSplit($saleId) as $code => $amount) {
            $this->move($code, $amount, 'sale', (int) $saleId, 'in');
        }
    }

    public function postTender($payment)
    {
        $amount = round((float) $payment->amount, 2);
        if ($amount <= 0) {
            return;
        }
        if (! empty($payment->purchase_id) && empty($payment->sale_id)) {
            $this->postPurchase($payment);

            return;
        }
        $code = $this->tenderCode($payment->paying_method);
        if ($code === '5211') {
            $this->move('5211', $amount, 'sale-momo', (int) $payment->id, 'in');
        } elseif ($code === '571') {
            $this->move('571', $amount, 'sale-cash', (int) $payment->id, 'in');
        } elseif ($code === '521') {
            $this->move('521', $amount, 'sale-bank', (int) $payment->id, 'in');
        }
    }

    public function postExpense($expense)
    {
        $this->ensure();
        $amount = round((float) $expense->amount, 2);
        if ($amount <= 0) {
            return;
        }
        $category = '';
        if ($expense->expense_category_id && Schema::hasTable('expense_categories')) {
            $category = (string) DB::table('expense_categories')->where('id', $expense->expense_category_id)->value('name');
        }
        $chosen = $expense->account_id ? Account::find($expense->account_id) : null;
        $code = ($chosen && $this->isExpenseCode($chosen->account_no)) ? (string) $chosen->account_no : $this->expenseCode($category, (string) $expense->note);
        if ($code === null) {
            $code = '658';
        }
        $this->move($code, $amount, 'expense', (int) $expense->id, 'in');
        $cash = '571';
        if (! empty($expense->credit_account_id)) {
            $credit = Account::find($expense->credit_account_id);
            if ($credit && in_array((string) $credit->account_no, array('521', '5211', '571'), true)) {
                $cash = (string) $credit->account_no;
            }
        } else {
            $paidBy = strtolower((string) $expense->payment_method.' '.(string) $expense->note);
            if (strpos($paidBy, 'momo') !== false || strpos($paidBy, 'orange') !== false) {
                $cash = '5211';
            }
        }
        $this->move($cash, $amount, 'expense-paid', (int) $expense->id, 'out');
    }

    public function syncSale($saleId)
    {
        $this->ensure();
        $this->postSale($saleId);
        $grand = round((float) DB::table('sales')->where('id', $saleId)->value('grand_total'), 2);
        if ($grand > 0) {
            $this->move('411', $grand, 'sale-receivable', (int) $saleId, 'in');
        }
        if (! Schema::hasTable('payments')) {
            return;
        }
        foreach (DB::table('payments')->where('sale_id', $saleId)->get() as $payment) {
            $this->postTender($payment);
            $amount = round((float) $payment->amount, 2);
            if ($amount > 0 && $this->tenderCode($payment->paying_method)) {
                $this->move('411', $amount, 'sale-clear', (int) $payment->id, 'out');
            }
        }
    }

    public function postPurchase($payment)
    {
        $this->ensure();
        $amount = round((float) $payment->amount, 2);
        if ($amount <= 0) {
            return;
        }
        $debit = '601';
        if (! empty($payment->account_id)) {
            $chosen = Account::find($payment->account_id);
            if ($chosen && $this->isExpenseCode($chosen->account_no)) {
                $debit = (string) $chosen->account_no;
            }
        }
        $credit = $this->tenderCode(isset($payment->paying_method) ? $payment->paying_method : '');
        if ($credit === null) {
            $credit = '571';
        }
        $this->move($debit, $amount, 'purchase', (int) $payment->id, 'in');
        $this->move($credit, $amount, 'purchase-paid', (int) $payment->id, 'out');
    }

    public function syncBooks()
    {
        $this->ensure();
        $this->bindTenant();
        if (Schema::hasTable('sales')) {
            foreach (DB::table('sales')->orderBy('id')->pluck('id') as $saleId) {
                $this->syncSale($saleId);
            }
        }
        if (Schema::hasTable('campay_payouts')) {
            foreach (DB::table('campay_payouts')->where('status', 'paid')->orderBy('id')->get() as $payout) {
                $this->postPayout($payout);
            }
        }
    }

    public function tenderCode($method)
    {
        $method = strtolower((string) $method);
        if ($method === '') {
            return null;
        }
        if (strpos($method, 'momo') !== false || strpos($method, 'orange') !== false) {
            return '5211';
        }
        if (strpos($method, 'cash') !== false) {
            return '571';
        }
        if (strpos($method, 'cheque') !== false || strpos($method, 'card') !== false || strpos($method, 'bank') !== false || strpos($method, 'paypal') !== false || strpos($method, 'visa') !== false) {
            return '521';
        }

        return null;
    }

    public function trialProof()
    {
        $debit = 0.0;
        $credit = 0.0;
        if (! Schema::hasTable('accounts')) {
            return array('debit' => 0, 'credit' => 0);
        }
        foreach ($this->catalog() as $row) {
            $balance = (float) Account::where('account_no', $row['code'])->value('total_balance');
            if ($this->balanceSide($row['code'], $row['normal']) === 'debit') {
                if ($balance >= 0) {
                    $debit += $balance;
                } else {
                    $credit += abs($balance);
                }
            } else {
                if ($balance >= 0) {
                    $credit += $balance;
                } else {
                    $debit += abs($balance);
                }
            }
        }

        return array('debit' => $debit, 'credit' => $credit);
    }

    public function postPayout($payout)
    {
        if (strtolower((string) $payout->status) !== 'paid') {
            return;
        }
        $this->ensure();
        $amount = round((float) $payout->amount, 2);
        if ($amount <= 0) {
            return;
        }
        $this->move('5211', $amount, 'payout', (int) $payout->id, 'out');
        if (isset($payout->kind) && $payout->kind === 'refund') {
            $this->move('7011', $amount, 'payout-refund', (int) $payout->id, 'out');

            return;
        }
        $code = $this->expenseCode((string) $payout->note, (string) $payout->person_name);
        if ($code !== null) {
            $this->move($code, $amount, 'payout-expense', (int) $payout->id, 'in');
        } else {
            $this->move('461', $amount, 'payout-drawings', (int) $payout->id, 'in');
        }
    }

    public function reverseMispostedOtherSales()
    {
        if (! Schema::hasTable('account_entries') || ! Schema::hasTable('accounts')) {
            return;
        }
        DB::transaction(function () {
            $added = (float) DB::table('account_entries')
                ->where('source', 'sale')
                ->where('account_no', '707')
                ->where('direction', 'in')
                ->sum('amount');
            $removed = (float) DB::table('account_entries')
                ->where('source', 'sale')
                ->where('account_no', '707')
                ->where('direction', 'out')
                ->sum('amount');
            $account = Account::where('account_no', '707')->lockForUpdate()->first();
            if ($account) {
                $account->total_balance = round((float) $account->total_balance - $added + $removed, 2);
                $account->save();
            }
            DB::table('account_entries')->where('source', 'sale')->where('account_no', '707')->delete();
        });
    }

    protected function bindTenant()
    {
        if (! class_exists(\App\Cloud\CloudTenant::class) || ! class_exists(\App\Services\Cloud\CloudTenantContext::class)) {
            return;
        }
        $tenant = \App\Cloud\CloudTenant::find(1);
        if ($tenant) {
            app(\App\Services\Cloud\CloudTenantContext::class)->set($tenant);
        }
    }

    public function backfill()
    {
        $this->ensure();
        if (! Schema::hasTable('sales')) {
            return;
        }
        $this->bindTenant();
        foreach (DB::table('sales')->orderBy('id')->pluck('id') as $saleId) {
            $this->postSale($saleId);
        }
        if (Schema::hasTable('payments')) {
            foreach (DB::table('payments')->whereNotNull('sale_id')->orderBy('id')->get() as $payment) {
                $this->postTender($payment);
            }
        }
        if (Schema::hasTable('expenses')) {
            foreach (\App\Expense::orderBy('id')->get() as $expense) {
                $this->postExpense($expense);
            }
        }
        if (Schema::hasTable('campay_payouts')) {
            foreach (DB::table('campay_payouts')->where('status', 'paid')->orderBy('id')->get() as $payout) {
                $this->postPayout($payout);
            }
        }
    }

    public function saleSplit($saleId)
    {
        $buckets = array();
        $lines = Schema::hasTable('product_sales')
            ? DB::table('product_sales')->where('sale_id', $saleId)->get()
            : array();
        if (count($lines) < 1) {
            $total = (float) DB::table('sales')->where('id', $saleId)->value('grand_total');
            if ($total > 0) {
                $buckets['7011'] = $total;
            }

            return $buckets;
        }
        foreach ($lines as $line) {
            $code = $this->revenueCode(isset($line->category_id) ? $line->category_id : null, isset($line->product_id) ? $line->product_id : null);
            if (! isset($buckets[$code])) {
                $buckets[$code] = 0;
            }
            $buckets[$code] += (float) $line->total;
        }

        return $buckets;
    }

    public function revenueCode($categoryId, $productId = null)
    {
        if (! $categoryId && $productId && Schema::hasTable('products')) {
            $categoryId = DB::table('products')->where('id', $productId)->value('category_id');
        }
        $department = null;
        try {
            $department = RestaurantDepartment::forCategoryId($categoryId);
        } catch (\Throwable $e) {
            $department = null;
        }
        if ($department === 'drinks') {
            return '7012';
        }
        if ($department === 'food') {
            return '7011';
        }
        $name = '';
        if ($categoryId && Schema::hasTable('categories')) {
            $name = strtolower((string) DB::table('categories')->where('id', $categoryId)->value('name'));
        }
        if (strpos($name, 'room') !== false || strpos($name, 'hotel') !== false || strpos($name, 'lodg') !== false) {
            return '7061';
        }
        if (strpos($name, 'conference') !== false || strpos($name, 'event') !== false || strpos($name, 'hall') !== false) {
            return '7062';
        }
        if (strpos($name, 'laundry') !== false) {
            return '7063';
        }
        if (strpos($name, 'spa') !== false || strpos($name, 'park') !== false || strpos($name, 'shuttle') !== false) {
            return '7064';
        }
        foreach (array('drink', 'bar', 'coffee', 'tea', 'whisk', 'wine', 'cocktail', 'smoothie', 'mocktail', 'spirit', 'beer', 'juice', 'cognac', 'vodka', 'gin', 'aperitif', 'champagne', 'soda', 'liquor', 'guinness', 'malt') as $needle) {
            if (strpos($name, $needle) !== false) {
                return '7012';
            }
        }
        if (strpos($name, 'food') !== false || strpos($name, 'restaurant') !== false || strpos($name, 'kitchen') !== false || strpos($name, 'meal') !== false) {
            return '7011';
        }
        if ($name !== '') {
            return '707';
        }

        return '7011';
    }

    public function expenseCode($name, $note = '')
    {
        $text = strtolower(trim($name.' '.$note));
        if (strpos($text, 'chill') !== false) {
            return '658';
        }
        $map = array(
            '661' => array('salar', 'wage', 'payroll', 'staff pay'),
            '664' => array('cnps', 'social charge'),
            '605' => array('water', 'electric', 'power', 'gas', 'fuel', 'eneo', 'diesel'),
            '604' => array('clean', 'toiletr', 'linen', 'housekeep'),
            '622' => array('rent', 'lease'),
            '624' => array('repair', 'maint'),
            '625' => array('insur'),
            '627' => array('advert', 'market', 'promo'),
            '628' => array('internet', 'telecom', 'wifi', 'airtime'),
            '631' => array('bank charge', 'campay', 'momo fee', 'orange fee'),
            '632' => array('commission', 'booking.com', 'agent'),
            '641' => array('license', 'property tax', 'patente', 'duty'),
            '671' => array('interest'),
            '681' => array('depreciat'),
            '695' => array('income tax'),
            '601' => array('kitchin', 'kitchen', 'restaurant', 'bar', 'food', 'beverage', 'drink'),
        );
        foreach ($map as $code => $words) {
            foreach ($words as $word) {
                if ($word !== '' && strpos($text, $word) !== false) {
                    return (string) $code;
                }
            }
        }

        return null;
    }

    protected function balanceSide($code, $normal)
    {
        if (in_array((string) $code, array('411', '4452', '461', '476', '811'), true) || $normal === 'Debit') {
            return 'debit';
        }

        return 'credit';
    }

    protected function isExpenseCode($code)
    {
        $code = (string) $code;

        return strlen($code) >= 3 && $code[0] === '6';
    }

    protected function move($accountNo, $amount, $source, $sourceId, $direction)
    {
        $amount = round((float) $amount, 2);
        if ($amount <= 0 || ! Schema::hasTable('accounts') || ! Schema::hasTable('account_entries')) {
            return;
        }
        $already = DB::table('account_entries')
            ->where('source', $source)
            ->where('source_id', $sourceId)
            ->where('account_no', (string) $accountNo)
            ->where('direction', $direction)
            ->exists();
        if ($already) {
            return;
        }
        DB::transaction(function () use ($accountNo, $amount, $source, $sourceId, $direction) {
            $account = Account::where('account_no', (string) $accountNo)->lockForUpdate()->first();
            if (! $account) {
                return;
            }
            $balance = (float) $account->total_balance;
            $account->total_balance = $direction === 'out' ? $balance - $amount : $balance + $amount;
            $account->save();
            DB::table('account_entries')->insert(array(
                'source' => $source,
                'source_id' => $sourceId,
                'account_no' => (string) $accountNo,
                'amount' => $amount,
                'direction' => $direction,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ));
        });
    }
}
