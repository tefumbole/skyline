<?php

namespace App\Support;

use App\Event;
use App\EventAssignment;
use App\Expense;
use App\Payment;
use App\Quotation;
use App\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class ExpenseBoard
{
    public static function canView($user)
    {
        if (! $user) {
            return false;
        }
        if (method_exists($user, 'hasFullAdminAccess') && $user->hasFullAdminAccess()) {
            return true;
        }

        return self::roleHas($user, 'expenses-index');
    }

    public static function canAdd($user)
    {
        if (! $user) {
            return false;
        }
        if (method_exists($user, 'hasFullAdminAccess') && $user->hasFullAdminAccess()) {
            return true;
        }

        return self::roleHas($user, 'expenses-add');
    }

    public static function canEdit($user)
    {
        if (! $user) {
            return false;
        }
        if (method_exists($user, 'hasFullAdminAccess') && $user->hasFullAdminAccess()) {
            return true;
        }

        return self::roleHas($user, 'expenses-edit');
    }

    protected static function roleHas($user, $permission)
    {
        try {
            $role = Role::find($user->role_id);

            return $role && $role->hasPermissionTo($permission);
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function events()
    {
        if (! Schema::hasTable('btw_events')) {
            return [];
        }
        $rows = Event::orderBy('event_start_at', 'desc')->limit(250)->get();
        $out = [];
        foreach ($rows as $event) {
            $when = $event->event_end_at ?: $event->event_start_at;
            $out[] = [
                'id' => (string) $event->id,
                'label' => trim($event->reference_no.' · '.$event->name),
                'when' => ($when && $when->lt(now())) ? 'Past' : 'Upcoming',
            ];
        }

        return $out;
    }

    public static function quotations()
    {
        if (! Schema::hasTable('quotations')) {
            return [];
        }
        $rows = Quotation::with('customer')->orderBy('id', 'desc')->limit(250)->get();
        $out = [];
        foreach ($rows as $quote) {
            $who = $quote->customer ? $quote->customer->name : 'Customer';
            $out[] = [
                'id' => (int) $quote->id,
                'label' => trim($quote->reference_no.' · '.$who),
            ];
        }

        return $out;
    }

    public static function paymentsFor($eventId, $quotationId)
    {
        $rows = [];
        $seen = [];
        if ($eventId && Schema::hasTable('btw_events') && Schema::hasColumn('payments', 'booking_id')) {
            $event = Event::find($eventId);
            if ($event && $event->booking_id) {
                foreach (Payment::where('booking_id', $event->booking_id)->orderBy('id', 'desc')->limit(40)->get() as $payment) {
                    $rows[] = self::paymentRow($payment);
                    $seen[(int) $payment->id] = true;
                }
            }
        }
        if ($quotationId && Schema::hasTable('quotations')) {
            $quote = Quotation::find((int) $quotationId);
            if ($quote) {
                $saleIds = self::saleIdsForQuotation($quote);
                if ($saleIds) {
                    foreach (Payment::whereIn('sale_id', $saleIds)->orderBy('id', 'desc')->limit(40)->get() as $payment) {
                        if (isset($seen[(int) $payment->id])) {
                            continue;
                        }
                        $rows[] = self::paymentRow($payment);
                    }
                }
            }
        }

        return $rows;
    }

    public static function engineersFor($eventId, $quotationId)
    {
        $eventIds = [];
        if ($eventId) {
            $eventIds[] = (string) $eventId;
        }
        if ($quotationId && Schema::hasTable('quotations') && Schema::hasTable('btw_events')) {
            $quote = Quotation::find((int) $quotationId);
            if ($quote) {
                foreach (self::eventIdsForQuotation($quote) as $id) {
                    $eventIds[] = (string) $id;
                }
            }
        }
        $eventIds = array_values(array_unique(array_filter($eventIds)));
        if (! $eventIds || ! Schema::hasTable('event_assignments')) {
            return [];
        }
        $assignments = EventAssignment::with(['workerProfile.customer', 'workerProfile.user', 'workerProfile.category', 'payments', 'event'])
            ->whereIn('event_id', $eventIds)
            ->get();
        $out = [];
        foreach ($assignments as $assignment) {
            $paid = 0;
            $status = 'Planned';
            if ($assignment->payments) {
                foreach ($assignment->payments as $payment) {
                    if ($payment->status === 'cancelled') {
                        continue;
                    }
                    $paid += (int) $payment->amount;
                    $status = $payment->statusLabel();
                }
            }
            $amount = $paid > 0 ? $paid : (int) $assignment->expected_total;
            if ($amount <= 0) {
                continue;
            }
            $profile = $assignment->workerProfile;
            $role = $profile && $profile->category ? $profile->category->name : ($profile ? $profile->specialization : 'Engineer');
            $out[] = [
                'name' => $profile ? $profile->displayName() : 'Engineer',
                'role' => $role ?: 'Engineer',
                'event' => $assignment->event ? $assignment->event->name : '',
                'amount' => $amount,
                'status' => $status,
            ];
        }

        return $out;
    }

    public static function syncPayments($expenseId, array $paymentIds)
    {
        if (! Schema::hasTable('expense_payment_links')) {
            return;
        }
        $ids = [];
        foreach ($paymentIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        DB::table('expense_payment_links')->where('expense_id', $expenseId)->delete();
        $now = date('Y-m-d H:i:s');
        foreach ($ids as $id) {
            DB::table('expense_payment_links')->insert([
                'expense_id' => (int) $expenseId,
                'payment_id' => $id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public static function linkedPaymentIds(array $expenseIds)
    {
        if (! $expenseIds || ! Schema::hasTable('expense_payment_links')) {
            return [];
        }
        $map = [];
        $rows = DB::table('expense_payment_links')->whereIn('expense_id', $expenseIds)->get();
        foreach ($rows as $row) {
            $map[(int) $row->expense_id][] = (int) $row->payment_id;
        }

        return $map;
    }

    public static function paymentLabels(array $paymentIds)
    {
        $paymentIds = array_values(array_unique(array_filter(array_map('intval', $paymentIds))));
        if (! $paymentIds) {
            return [];
        }
        $out = [];
        foreach (Payment::whereIn('id', $paymentIds)->get() as $payment) {
            $out[(int) $payment->id] = self::paymentRow($payment)['label'];
        }

        return $out;
    }

    protected static function paymentRow(Payment $payment)
    {
        $ref = $payment->payment_reference ?: ('Payment '.$payment->id);
        $method = $payment->paying_method ?: 'Payment';

        return [
            'id' => (int) $payment->id,
            'amount' => (float) $payment->amount,
            'label' => $ref.' · '.number_format((float) $payment->amount, 0).' XAF · '.$method,
        ];
    }

    protected static function saleIdsForQuotation(Quotation $quote)
    {
        if (! Schema::hasTable('sales') || ! $quote->reference_no) {
            return [];
        }
        $ref = $quote->reference_no;
        $query = Sale::query();
        if ($quote->customer_id) {
            $query->where('customer_id', $quote->customer_id);
        }
        $query->where(function ($q) use ($ref) {
            $q->where('reference_no', $ref)
                ->orWhere('sale_note', 'like', '%'.$ref.'%')
                ->orWhere('staff_note', 'like', '%'.$ref.'%');
        });
        $ids = $query->orderBy('id', 'desc')->limit(30)->pluck('id')->all();
        if ($ids || ! $quote->customer_id) {
            return $ids;
        }

        return Sale::where('customer_id', $quote->customer_id)
            ->orderBy('id', 'desc')
            ->limit(15)
            ->pluck('id')
            ->all();
    }

    protected static function eventIdsForQuotation(Quotation $quote)
    {
        $ids = [];
        if ($quote->customer_id) {
            $ids = Event::where('customer_id', $quote->customer_id)->pluck('id')->all();
        }
        $ref = $quote->reference_no;
        if ($ref) {
            $more = Event::where(function ($q) use ($ref) {
                $q->where('internal_notes', 'like', '%'.$ref.'%')
                    ->orWhere('internal_description', 'like', '%'.$ref.'%')
                    ->orWhere('name', 'like', '%'.$ref.'%')
                    ->orWhere('reference_no', $ref);
            })->pluck('id')->all();
            foreach ($more as $id) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
