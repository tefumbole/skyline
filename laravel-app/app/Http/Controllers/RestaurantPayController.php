<?php

namespace App\Http\Controllers;

use App\Payment;
use App\Sale;
use App\Services\Restaurant\RestaurantPayments;
use App\Support\SalePayQr;
use Illuminate\Http\Request;

class RestaurantPayController extends Controller
{
    public function show($sale, $token)
    {
        $sale = $this->sale($sale, $token);
        $paidAmount = (float) $sale->paid_amount;
        $due = max(0, (float) $sale->grand_total - $paidAmount);
        $empty = (float) $sale->grand_total <= 0.009 && $paidAmount <= 0.009;

        return view('restaurant.pay', [
            'sale' => $sale,
            'due' => $due,
            'empty' => $empty,
            'paid' => ! $empty && ($paidAmount > 0.009 && ((int) $sale->payment_status === 4 || $due <= 0)),
        ]);
    }

    public function start(Request $request, $sale, $token)
    {
        $sale = $this->sale($sale, $token);
        if ((int) $sale->payment_status === 4) {
            return redirect()->route('restaurant.pay', [$sale->id, SalePayQr::token($sale)])
                ->with('message', 'This invoice is already paid.');
        }
        $due = max(0, (float) $sale->grand_total - (float) $sale->paid_amount);
        if ($due <= 0) {
            return back()->with('message', 'This invoice is already paid.');
        }
        $customer = $sale->customer;
        $phone = $customer ? $customer->phone_number : '';
        $campay = $this->campayBearerToken();
        if (! $campay) {
            return back()->with('not_permitted', 'Mobile money is not configured yet. Please pay at the cashier.');
        }
        $link = $this->mobileMoneyRequestLink(
            $campay,
            $due,
            route('restaurant.pay.callback'),
            $sale->id,
            $phone
        );
        if ($link == false) {
            return back()->with('not_permitted', 'The payment page could not be opened. Please try again or pay at the cashier.');
        }

        return redirect()->away($link);
    }

    public function callback(Request $request)
    {
        $saleId = $request->input('external_reference');
        $sale = Sale::find($saleId);
        if (! $sale) {
            return redirect('/')->with('not_permitted', 'Invoice not found.');
        }
        $token = SalePayQr::token($sale);
        $back = redirect()->route('restaurant.pay', [$sale->id, $token]);
        $reference = trim((string) $request->input('reference', ''));
        $verified = $reference !== ''
            ? $this->verifyCampayTransaction($reference, null, null, (string) $sale->id)
            : null;
        if ($verified) {
            $result = RestaurantPayments::recordGateway($sale, $verified['amount'], $reference, null);
            if ($result === 'duplicate' || $result === 'paid' || $result === 'recorded') {
                return $back->with('message', 'Payment received. Thank you.');
            }
        }
        $status = strtoupper(trim((string) $request->input('status', '')));
        if ($status === '' || $status === 'PENDING') {
            return $back->with('message', 'Waiting for Mobile Money. The bill stays unpaid until Campay confirms it.');
        }

        return $back->with('not_permitted', 'Payment was not completed. The bill is still unpaid.');
    }

    protected function sale($id, $token)
    {
        $sale = Sale::with('customer')->findOrFail($id);
        if (! SalePayQr::isValid($sale, $token)) {
            abort(404);
        }

        return $sale;
    }

    public function webhook(Request $request)
    {
        $reference = trim((string) $request->input('reference', ''));
        $external = trim((string) $request->input('external_reference', ''));
        if ($reference === '') {
            return response('ok', 200);
        }
        if (preg_match('/^[a-f0-9]{20}$/', $external)) {
            app(\App\Http\Controllers\SaleController::class)->completePosMomo($external);

            return response('ok', 200);
        }
        $pending = null;
        $sale = null;
        if (strpos($external, 'sale-momo-') === 0) {
            $pending = \Illuminate\Support\Facades\Cache::get($external);
            if (is_array($pending) && ! empty($pending['sale_id'])) {
                $sale = Sale::find($pending['sale_id']);
            }
        } elseif (ctype_digit($external)) {
            $sale = Sale::find((int) $external);
        }
        if ($sale) {
            $verified = $this->verifyCampayTransaction($reference, null, null, $external);
            if ($verified) {
                RestaurantPayments::recordGateway(
                    $sale,
                    $verified['amount'],
                    $reference,
                    is_array($pending) && isset($pending['user_id']) ? $pending['user_id'] : null
                );
                if (strpos($external, 'sale-momo-') === 0) {
                    \Illuminate\Support\Facades\Cache::forget($external);
                }
            }
        }

        return response('ok', 200);
    }
}
