<?php

namespace App\Support;

use App\CampayPayout;
use App\CampayPayoutRequest;
use App\Deposit;
use App\Payment;
use App\PublicDonation;
use App\Sale;
use App\User;
use Illuminate\Support\Facades\Schema;

/**
 * One list of Mobile Money movements already stored as deposits, bill payments, and payouts.
 */
class MomoLedger
{
    public static function shortNote($note)
    {
        $text = (string) $note;
        $bits = array();
        if (stripos($text, 'bill') !== false || stripos($text, 'posr-') !== false) {
            $bits[] = 'Bill POS';
        }
        if (stripos($text, 'campay') !== false) {
            $bits[] = 'Campay';
        }
        if (stripos($text, 'skyline') !== false) {
            $bits[] = 'Skyline';
        }

        return implode(' · ', $bits);
    }

    public static function history()
    {
        $rows = array();
        $userIds = array();

        foreach (self::deposits() as $row) {
            $rows[] = $row;
            if ($row['user_id']) {
                $userIds[] = $row['user_id'];
            }
        }
        foreach (self::billPayments() as $row) {
            $rows[] = $row;
            if ($row['user_id']) {
                $userIds[] = $row['user_id'];
            }
        }
        foreach (self::withdrawals() as $row) {
            $rows[] = $row;
            if ($row['user_id']) {
                $userIds[] = $row['user_id'];
            }
        }
        foreach (self::donations(self::depositReferences($rows)) as $row) {
            $rows[] = $row;
        }

        $names = self::userLabels($userIds);
        foreach ($rows as $index => $row) {
            if ($row['done_by'] === '' && $row['user_id'] && isset($names[$row['user_id']])) {
                $rows[$index]['done_by'] = $names[$row['user_id']];
            }
            unset($rows[$index]['user_id']);
        }

        usort($rows, function ($a, $b) {
            if ($a['sort'] === $b['sort']) {
                return 0;
            }

            return $a['sort'] > $b['sort'] ? -1 : 1;
        });

        return array_slice($rows, 0, 2000);
    }

    protected static function deposits()
    {
        if (! Schema::hasTable('deposits')) {
            return array();
        }
        $query = Deposit::with('customer')->orderByDesc('id');
        if (Schema::hasColumn('deposits', 'payment_method')) {
            $query->where('payment_method', 3);
        }
        $rows = array();
        foreach ($query->limit(1500)->get() as $deposit) {
            $status = self::depositStatus($deposit);
            $rows[] = self::row(
                $deposit->created_at,
                'deposit',
                (float) $deposit->amount,
                (int) $deposit->user_id,
                '',
                (string) $deposit->mtn_number,
                $deposit->customer ? (string) $deposit->customer->name : '',
                $status['label'],
                $status['tone'],
                (string) $deposit->note,
                trim((string) $deposit->campay_reference)
            );
        }

        return $rows;
    }

    protected static function billPayments()
    {
        if (! Schema::hasTable('payments') || ! Schema::hasColumn('payments', 'paying_method')) {
            return array();
        }
        $payments = Payment::query()
            ->where(function ($query) {
                $query->whereRaw('LOWER(paying_method) like ?', array('%momo%'))
                    ->orWhereRaw('LOWER(paying_method) like ?', array('%orange%'));
            })
            ->orderByDesc('id')
            ->limit(1500)
            ->get();
        $saleIds = array();
        foreach ($payments as $payment) {
            if ($payment->sale_id) {
                $saleIds[] = (int) $payment->sale_id;
            }
        }
        $sales = array();
        if ($saleIds && Schema::hasTable('sales')) {
            foreach (Sale::with('customer')->whereIn('id', array_values(array_unique($saleIds)))->get() as $sale) {
                $sales[$sale->id] = $sale;
            }
        }
        $taken = array();
        if (Schema::hasTable('deposits') && Schema::hasColumn('deposits', 'campay_reference')) {
            foreach (Deposit::query()->whereNotNull('campay_reference')->orderByDesc('id')->limit(1500)->pluck('campay_reference') as $reference) {
                $reference = trim((string) $reference);
                if ($reference !== '') {
                    $taken[strtolower($reference)] = true;
                }
            }
        }
        $rows = array();
        foreach ($payments as $payment) {
            $note = trim((string) $payment->payment_note);
            if (preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', $note, $match) && isset($taken[strtolower($match[0])])) {
                continue;
            }
            $sale = isset($sales[$payment->sale_id]) ? $sales[$payment->sale_id] : null;
            $customer = $sale && $sale->customer ? $sale->customer : null;
            $phone = $customer && isset($customer->phone_number) ? (string) $customer->phone_number : '';
            if ($sale && $sale->reference_no) {
                $note = trim('Bill '.$sale->reference_no.($note !== '' ? ' · '.$note : ''));
            }
            $rows[] = self::row(
                $payment->created_at,
                'deposit',
                (float) $payment->amount,
                (int) $payment->user_id,
                '',
                $phone,
                $customer ? (string) $customer->name : '',
                'Paid',
                'ok',
                $note,
                ''
            );
        }

        return $rows;
    }

    protected static function withdrawals()
    {
        if (! Schema::hasTable('campay_payouts')) {
            return array();
        }
        $payouts = CampayPayout::query()->orderByDesc('id')->limit(1500)->get();
        $requestIds = array();
        foreach ($payouts as $payout) {
            if (! empty($payout->request_id)) {
                $requestIds[] = (int) $payout->request_id;
            }
        }
        $requests = array();
        if ($requestIds && Schema::hasTable('campay_payout_requests')) {
            foreach (CampayPayoutRequest::whereIn('id', array_values(array_unique($requestIds)))->get() as $request) {
                $requests[$request->id] = $request;
            }
        }
        $rows = array();
        foreach ($payouts as $payout) {
            $request = isset($requests[$payout->request_id]) ? $requests[$payout->request_id] : null;
            $status = self::payoutStatus($payout->status);
            $note = trim((string) $payout->note);
            if (! empty($payout->operator)) {
                $note = trim($note.($note !== '' ? ' · ' : '').$payout->operator);
            }
            $rows[] = self::row(
                $payout->created_at,
                'withdrawal',
                (float) $payout->amount,
                (int) $payout->user_id,
                '',
                (string) $payout->phone,
                $request ? (string) $request->requester_name : '',
                $status['label'],
                $status['tone'],
                $note,
                ''
            );
        }

        return $rows;
    }

    protected static function donations(array $usedReferences)
    {
        if (! Schema::hasTable('public_donations')) {
            return array();
        }
        $rows = array();
        $donations = PublicDonation::query()->orderByDesc('id')->limit(400)->get();
        foreach ($donations as $donation) {
            $method = strtolower((string) $donation->method);
            if ($method === 'visa' || $method === 'crypto') {
                continue;
            }
            $reference = trim((string) $donation->campay_reference);
            if ($reference !== '' && isset($usedReferences[$reference])) {
                continue;
            }
            $status = self::donationStatus($donation->status);
            $rows[] = self::row(
                $donation->created_at,
                'deposit',
                (float) $donation->amount,
                0,
                (string) $donation->person_name,
                (string) $donation->phone,
                (string) $donation->person_name,
                $status['label'],
                $status['tone'],
                (string) $donation->note,
                $reference
            );
        }

        return $rows;
    }

    protected static function depositReferences(array $rows)
    {
        $used = array();
        foreach ($rows as $row) {
            if ($row['reference'] !== '') {
                $used[$row['reference']] = true;
            }
        }

        return $used;
    }

    protected static function userLabels(array $ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (! $ids || ! Schema::hasTable('users')) {
            return array();
        }
        $columns = array('id', 'name');
        if (Schema::hasColumn('users', 'username')) {
            $columns[] = 'username';
        }
        $labels = array();
        foreach (User::whereIn('id', $ids)->get($columns) as $user) {
            $username = isset($user->username) ? trim((string) $user->username) : '';
            $labels[$user->id] = $username !== '' ? $username : (string) $user->name;
        }

        return $labels;
    }

    protected static function row($at, $direction, $amount, $userId, $doneBy, $number, $requestedBy, $status, $tone, $note, $reference)
    {
        $stamp = $at ? $at->getTimestamp() : 0;

        return array(
            'sort' => $stamp,
            'when' => $at ? $at->format('M j H:i') : '',
            'direction' => $direction,
            'amount' => $amount,
            'user_id' => $userId,
            'done_by' => $doneBy,
            'number' => $number,
            'requested_by' => $requestedBy,
            'status' => $status,
            'tone' => $tone,
            'note' => $note,
            'reference' => $reference,
        );
    }

    protected static function depositStatus($deposit)
    {
        $status = (int) $deposit->status;
        if ($status === 1) {
            return array('label' => 'Paid', 'tone' => 'ok');
        }
        if ($status === 2) {
            return array('label' => 'Not paid', 'tone' => 'no');
        }

        return array('label' => 'Waiting', 'tone' => 'wait');
    }

    protected static function payoutStatus($status)
    {
        $status = strtolower((string) $status);
        if ($status === 'paid') {
            return array('label' => 'Paid', 'tone' => 'ok');
        }
        if ($status === 'pending') {
            return array('label' => 'Waiting', 'tone' => 'wait');
        }
        if ($status === 'rejected') {
            return array('label' => 'Rejected', 'tone' => 'no');
        }

        return array('label' => 'Not paid', 'tone' => 'no');
    }

    protected static function donationStatus($status)
    {
        $status = strtolower((string) $status);
        if ($status === 'paid') {
            return array('label' => 'Paid', 'tone' => 'ok');
        }
        if ($status === 'failed') {
            return array('label' => 'Not paid', 'tone' => 'no');
        }

        return array('label' => 'Waiting', 'tone' => 'wait');
    }
}
