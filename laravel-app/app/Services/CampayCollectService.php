<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Campay collection for POS. A prompt is not a payment.
 * Paid means Campay confirmed the debit on the phone.
 */
class CampayCollectService
{
    public function momoNumber($phone)
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (strpos($digits, '237') === 0) {
            $digits = substr($digits, 3);
        }
        if (strlen($digits) !== 9 || $digits[0] !== '6') {
            return null;
        }
        $three = substr($digits, 0, 3);
        $two = substr($digits, 0, 2);
        $mtn = $two === '67' || ($three >= '650' && $three <= '654') || ($three >= '680' && $three <= '683');
        $orange = $two === '69' || ($three >= '655' && $three <= '659') || ($three >= '690' && $three <= '699');
        if (! $mtn && ! $orange) {
            return null;
        }

        return '237'.$digits;
    }

    public function collect($phone, $amount, $reference, $description)
    {
        return $this->call('POST', 'collect/', [
            'amount' => (string) (int) $amount,
            'currency' => 'XAF',
            'from' => (string) $phone,
            'description' => (string) $description,
            'external_reference' => (string) $reference,
            'webhook' => url('/pay/campay/webhook'),
        ]);
    }

    public function transaction($reference)
    {
        $reference = trim((string) $reference);
        if ($reference === '') {
            return null;
        }

        return $this->call('GET', 'transaction/'.$reference.'/', []);
    }

    /**
     * @return array{state:string,confirmed:bool,reason:string}
     */
    public function collectionOutcome($reference, $expectedAmount, $expectedPhone = null, $externalPattern = null)
    {
        $body = $this->transaction($reference);
        $http = is_array($body) ? (int) (isset($body['_http']) ? $body['_http'] : 0) : 0;
        $reason = is_array($body) && isset($body['reason']) ? (string) $body['reason'] : '';
        if (! is_array($body) || $http !== 200) {
            return ['state' => 'pending', 'confirmed' => false, 'reason' => $reason];
        }

        $status = strtoupper(trim((string) (isset($body['status']) ? $body['status'] : '')));
        if ($status === 'SUCCESSFUL') {
            if (! $this->collectionDebited($body, $expectedAmount, $expectedPhone, $externalPattern)) {
                Log::warning('Campay success was not a confirmed debit', [
                    'reference' => $reference,
                    'amount' => isset($body['amount']) ? $body['amount'] : null,
                    'expected' => (int) $expectedAmount,
                ]);

                return ['state' => 'pending', 'confirmed' => false, 'reason' => $reason];
            }

            return ['state' => 'paid', 'confirmed' => true, 'reason' => $reason];
        }

        if (in_array($status, ['FAILED', 'CANCELLED', 'CANCELED', 'REJECTED', 'EXPIRED'], true)) {
            return ['state' => 'failed', 'confirmed' => true, 'reason' => $reason];
        }

        return ['state' => 'pending', 'confirmed' => true, 'reason' => $reason];
    }

    public function payerFailure($reason, $amount)
    {
        $reason = strtoupper(trim((string) $reason));
        $money = number_format((int) $amount);
        if (strpos($reason, 'LOW_BALANCE') !== false || strpos($reason, 'INSUFFICIENT') !== false) {
            return 'The Mobile Money payment of '.$money.' XAF failed because the number does not have enough money.';
        }
        if (strpos($reason, 'LIMIT') !== false || strpos($reason, 'NOT_ALLOWED') !== false) {
            return 'The Mobile Money payment of '.$money.' XAF failed because the operator blocked this number.';
        }
        if ($reason === '' || strpos($reason, 'CANCEL') !== false || strpos($reason, 'TIMEOUT') !== false || strpos($reason, 'EXPIRED') !== false || strpos($reason, 'NOT_APPROV') !== false || strpos($reason, 'DENIED') !== false || strpos($reason, 'REJECT') !== false) {
            return 'The Mobile Money payment of '.$money.' XAF was not approved. The order was not placed.';
        }

        return 'The Mobile Money payment of '.$money.' XAF failed. The order was not placed.';
    }

    public function collectError($body)
    {
        if (is_array($body)) {
            foreach (['reason', 'message', 'detail', 'error'] as $key) {
                if (! empty($body[$key]) && is_string($body[$key])) {
                    return 'Campay could not send the payment: '.substr($body[$key], 0, 180);
                }
            }
        }

        return 'Campay could not send the payment to this number.';
    }

    public function collectionDebited(array $body, $expectedAmount, $expectedPhone = null, $externalPattern = null)
    {
        if (! isset($body['amount']) || ! is_numeric($body['amount'])) {
            return false;
        }
        if ($expectedAmount !== null && (int) round((float) $body['amount']) !== (int) $expectedAmount) {
            return false;
        }
        if ((float) $body['amount'] <= 0) {
            return false;
        }
        $currency = strtoupper(trim((string) (isset($body['currency']) ? $body['currency'] : 'XAF')));
        if ($currency !== 'XAF') {
            return false;
        }
        if (trim((string) (isset($body['operator_reference']) ? $body['operator_reference'] : '')) === '') {
            return false;
        }
        $external = trim((string) (isset($body['external_reference']) ? $body['external_reference'] : ''));
        if ($externalPattern && ($external === '' || ! preg_match($externalPattern, $external))) {
            return false;
        }
        if ($expectedPhone) {
            $got = preg_replace('/\D/', '', (string) (isset($body['phone_number']) ? $body['phone_number'] : (isset($body['from']) ? $body['from'] : '')));
            $want = substr(preg_replace('/\D/', '', (string) $expectedPhone), -9);
            $got9 = $got !== '' ? substr($got, -9) : '';
            if ($got9 !== '' && $want !== '' && $got9 !== $want) {
                return false;
            }
        }

        return true;
    }

    protected function call($method, $path, array $payload)
    {
        $token = $this->token();
        if ($token === '') {
            Log::warning('Campay collect has no token');

            return null;
        }
        $base = rtrim((string) config('services.campay.base_url', 'https://www.campay.net/api'), '/');
        $ch = curl_init($base.'/'.ltrim($path, '/'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Token '.$token,
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
        $raw = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded)) {
            Log::warning('Campay collect bad response', ['path' => $path, 'http' => $http]);

            return null;
        }
        $decoded['_http'] = $http;
        if ($http >= 400 && ! isset($decoded['status'])) {
            $decoded['status'] = 'FAILED';
        }

        return $decoded;
    }

    protected function token()
    {
        $static = config('services.campay.token');

        return $static ? (string) $static : '';
    }
}
