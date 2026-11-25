<?php

namespace App\Support;

use App\Account;
use App\CampayPayout;
use App\Expense;
use App\Payment;
use Illuminate\Support\Facades\DB;

class ChartPosting
{
    public static function register()
    {
        Payment::creating(function ($payment) {
            if (! $payment->sale_id) {
                return;
            }
            try {
                $chart = app(ChartOfAccounts::class);
                $target = $chart->revenueAccountForSale($payment->sale_id);
            } catch (\Throwable $e) {
                return;
            }
            if (! $target) {
                return;
            }
            $current = $payment->account_id ? Account::find($payment->account_id) : null;
            $automatic = array('CASH-001', '571', '5211', '7011');
            if (! $current || $current->is_default || in_array((string) $current->account_no, $automatic, true)) {
                $payment->account_id = $target->id;
            }
        });

        Payment::created(function ($payment) {
            try {
                $chart = app(ChartOfAccounts::class);
                if ($payment->sale_id) {
                    $chart->postSale($payment->sale_id);
                }
                $chart->postTender($payment);
            } catch (\Throwable $e) {
            }
        });

        Expense::creating(function ($expense) {
            try {
                $account = app(ChartOfAccounts::class)->expenseAccountFor('', (string) $expense->note);
                $category = '';
                if ($expense->expense_category_id) {
                    $category = (string) DB::table('expense_categories')->where('id', $expense->expense_category_id)->value('name');
                    $account = app(ChartOfAccounts::class)->expenseAccountFor($category, (string) $expense->note);
                }
            } catch (\Throwable $e) {
                return;
            }
            if (! $account) {
                return;
            }
            $current = $expense->account_id ? Account::find($expense->account_id) : null;
            $code = $current ? (string) $current->account_no : '';
            if (! $current || $code === '' || $code[0] !== '6') {
                $expense->account_id = $account->id;
            }
        });

        Expense::created(function ($expense) {
            try {
                app(ChartOfAccounts::class)->postExpense($expense);
            } catch (\Throwable $e) {
            }
        });

        CampayPayout::saved(function ($payout) {
            try {
                app(ChartOfAccounts::class)->postPayout($payout);
            } catch (\Throwable $e) {
            }
        });
    }
}
