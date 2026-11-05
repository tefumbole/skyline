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
        $amount = (int) round((float) $request->input('amount', $due));
        if ($amount < 100 || $amount > (int) round($due) + 1) {
            return back()->with('not_permitted', 'Enter a whole amount of at least 100 XAF, up to the amount still due. Nothing was charged.');
        }
        $customer = $sale->customer;
        $campay = app(\App\Services\CampayCollectService::class);
        $phone = $campay->momoNumber($request->input('momo_phone') ?: ($customer ? $customer->phone_number : ''));
        if (! $phone) {
            return back()->with('not_permitted', 'Enter an MTN or Orange Cameroon number. Nothing was charged.');
        }
        $external = 'sale-momo-'.$sale->id.'-'.date('His');
        $body = $campay->collect($phone, $amount, $external, 'Skyline '.$sale->reference_no);
        $reference = is_array($body) && ! empty($body['reference']) ? (string) $body['reference'] : '';
        if ($reference === '') {
            return back()->with('not_permitted', $campay->collectError($body).' Nothing was charged.');
        }
        $pending = [
            'sale_id' => $sale->id,
            'amount' => $amount,
            'reference' => $reference,
            'phone' => $phone,
            'external' => $external,
        ];
        \Illuminate\Support\Facades\Cache::put($external, $pending, now()->addMinutes(30));
        \Illuminate\Support\Facades\Cache::put('invoice-momo:'.$sale->id.':'.$token, $pending, now()->addMinutes(30));

        return view('restaurant.pay', [
            'sale' => $sale,
            'due' => $due,
            'empty' => false,
            'paid' => false,
            'waiting' => true,
            'amount' => $amount,
            'statusUrl' => route('restaurant.pay.status', [$sale->id, $token]),
        ]);
    }

    public function status($sale, $token)
    {
        $sale = $this->sale($sale, $token);
        $key = 'invoice-momo:'.$sale->id.':'.$token;
        $pending = \Illuminate\Support\Facades\Cache::get($key);
        if ((int) $sale->payment_status === 4 && (float) $sale->paid_amount > 0.009) {
            return response()->json(['status' => 'paid']);
        }
        if (! is_array($pending)) {
            return response()->json(['status' => 'failed', 'message' => 'This payment request has expired. Nothing was marked paid.']);
        }
        if (RestaurantPayments::hasReference($sale->id, $pending['reference'])) {
            return response()->json(['status' => 'paid']);
        }
        $campay = app(\App\Services\CampayCollectService::class);
        $pattern = '/^'.preg_quote($pending['external'], '/').'$/';
        $outcome = $campay->collectionOutcome($pending['reference'], $pending['amount'], $pending['phone'], $pattern);
        if ($outcome['state'] === 'paid' && ! empty($outcome['confirmed'])) {
            $result = RestaurantPayments::recordGateway($sale, $pending['amount'], $pending['reference'], null);
            if (in_array($result, ['recorded', 'duplicate', 'paid'], true)) {
                \Illuminate\Support\Facades\Cache::forget($key);

                return response()->json(['status' => 'paid']);
            }

            return response()->json(['status' => 'pending']);
        }
        if ($outcome['state'] === 'failed') {
            \Illuminate\Support\Facades\Cache::forget($key);

            return response()->json([
                'status' => 'failed',
                'message' => $campay->payerFailure(isset($outcome['reason']) ? $outcome['reason'] : '', $pending['amount']),
            ]);
        }

        return response()->json(['status' => 'pending']);
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
        if ($this->settleIncomingCollect($reference, $external)) {
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
                $result = RestaurantPayments::recordGateway(
                    $sale,
                    $verified['amount'],
                    $reference,
                    is_array($pending) && isset($pending['user_id']) ? $pending['user_id'] : null
                );
                if ($result === 'recorded' && is_array($pending) && ! empty($pending['table_note'])) {
                    app(SaleController::class)->stampTablePayment(
                        $sale->id,
                        $reference,
                        $pending['table_note'],
                        isset($pending['amount']) ? $pending['amount'] : $verified['amount']
                    );
                }
                if (strpos($external, 'sale-momo-') === 0) {
                    \Illuminate\Support\Facades\Cache::forget($external);
                }
            }
        }

        return response('ok', 200);
    }

    protected function settleIncomingCollect($reference, $external)
    {
        $donation = \App\PublicDonation::where('campay_reference', $reference)->first();
        if (! $donation && preg_match('/^(don|dep)-\d+-(\d+)$/', $external, $match)) {
            $donation = \App\PublicDonation::find((int) $match[2]);
        }
        if ($donation && $donation->status !== 'paid') {
            app(\App\Http\Controllers\PublicDonateController::class)->applyGateway($donation);

            return true;
        }
        $deposit = \App\Deposit::where('campay_reference', $reference)->where('status', 0)->first();
        if ($deposit) {
            app(\App\Http\Controllers\PaymentController::class)->settleFromGateway($deposit);

            return true;
        }

        return false;
    }
}
