<?php

namespace App\Services\Coth;

use App\CothReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class CothPaymentService
{
    public function momoConfigured()
    {
        return (bool) (config('services.campay.token') ?: getenv('CAMPAY_TOKEN') ?: getenv('MOMO_TOKEN'));
    }

    public function cardConfigured()
    {
        return (bool) (config('services.stripe.secret') ?: getenv('STRIPE_SECRET'));
    }

    public function start(CothReservation $reservation, $method)
    {
        if ($method === 'card') {
            if (! $this->cardConfigured()) {
                throw new \RuntimeException('Card payment is not configured.');
            }

            return $this->stripeLink($reservation);
        }
        if (! $this->momoConfigured()) {
            throw new \RuntimeException('Mobile money is not configured.');
        }

        return $this->campayLink($reservation);
    }

    public function handleCampay(Request $request, CothReservationService $reservations)
    {
        $reservation = $this->reservationFromExternal($request->input('external_reference'));
        if (! $reservation && $request->filled('reservation')) {
            $reservation = CothReservation::where('public_token', $request->input('reservation'))->first();
        }
        if (! $reservation) {
            return null;
        }
        $status = strtoupper((string) $request->input('status'));
        $successful = $status === 'SUCCESSFUL';
        $amount = $request->filled('amount') ? (int) $request->input('amount') : ($successful ? (int) $reservation->amount_due : 0);
        $reference = (string) ($request->input('reference') ?: $request->input('transaction_id') ?: '');
        $reservations->applyGateway($reservation, $successful, $amount, $reference, 'momo');

        return $reservation->fresh();
    }

    public function handleStripe($sessionId, CothReservationService $reservations)
    {
        $secret = config('services.stripe.secret') ?: getenv('STRIPE_SECRET');
        if (! $secret || ! $sessionId) {
            return null;
        }
        Stripe::setApiKey($secret);
        try {
            $session = StripeSession::retrieve($sessionId);
        } catch (\Throwable $e) {
            Log::info('COTH Stripe retrieve failed: '.$e->getMessage());

            return null;
        }
        $reservationId = isset($session->metadata['reservation_id']) ? $session->metadata['reservation_id'] : null;
        $reservation = $reservationId ? CothReservation::find($reservationId) : CothReservation::where('stripe_session_id', $sessionId)->first();
        if (! $reservation) {
            return null;
        }
        $paid = isset($session->payment_status) && $session->payment_status === 'paid';
        $amount = isset($session->amount_total) ? (int) $session->amount_total : 0;
        $reservations->applyGateway($reservation, $paid, $amount, (string) $sessionId, 'card');

        return $reservation->fresh();
    }

    protected function campayLink(CothReservation $reservation)
    {
        $token = config('services.campay.token') ?: getenv('CAMPAY_TOKEN') ?: getenv('MOMO_TOKEN');
        $payload = json_encode([
            'amount' => (string) $reservation->amount_due,
            'from' => preg_replace('/\D/', '', (string) $reservation->phone),
            'currency' => 'XAF',
            'external_reference' => (string) $reservation->id,
            'redirect_url' => route('coth.campay'),
            'failure_redirect_url' => route('coth.campay'),
            'payment_options' => 'MOMO',
        ]);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://www.campay.net/api/get_payment_link/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Authorization: Token '.$token,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);
        $raw = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            throw new \RuntimeException('Could not start mobile money.');
        }
        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded) || empty($decoded['link'])) {
            Log::info('COTH Campay link failed', ['body' => substr((string) $raw, 0, 400)]);
            throw new \RuntimeException('Could not start mobile money.');
        }

        return $decoded['link'];
    }

    protected function stripeLink(CothReservation $reservation)
    {
        $secret = config('services.stripe.secret') ?: getenv('STRIPE_SECRET');
        Stripe::setApiKey($secret);
        $table = $reservation->table;
        $session = StripeSession::create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'xaf',
                    'unit_amount' => (int) $reservation->amount_due,
                    'product_data' => [
                        'name' => 'Chill on the Hill '.($table ? $table->code : 'table'),
                        'description' => $reservation->event_date->format('j F Y').' · one day · '.$reservation->reference,
                    ],
                ],
                'quantity' => 1,
            ]],
            'success_url' => route('coth.stripe', [], true).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('coth.checkout', $reservation->public_token),
            'metadata' => [
                'reservation_id' => (string) $reservation->id,
                'module' => 'coth',
            ],
        ]);
        $reservation->stripe_session_id = $session->id;
        $reservation->payment_status = 'pending';
        $reservation->save();

        return $session->url;
    }

    protected function reservationFromExternal($external)
    {
        $id = (int) explode(',', (string) $external)[0];

        return $id > 0 ? CothReservation::find($id) : null;
    }
}
