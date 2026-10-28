<?php

namespace App\Services\Coth;

use Illuminate\Support\Facades\Cache;

/**
 * Look up the holder name a Cameroonian number is registered under at Campay.
 *
 * Uses the credentials already configured for Campay payments (either an
 * access token or a username/password pair) and caches the exchanged token
 * for an hour so one call to this method does not do two round trips.
 */
class CothHolderLookup
{
    public function name($phoneDigits)
    {
        $digits = preg_replace('/\D/', '', (string) $phoneDigits);
        if ($digits === '' || substr($digits, 0, 3) !== '237') {
            return '';
        }
        $token = $this->accessToken();
        if ($token === '') {
            return '';
        }
        $url = $this->base().'/holder_info/?phone_number='.rawurlencode($digits);
        list($status, $body) = $this->get($url, [
            'Accept: application/json',
            'Authorization: Token '.$token,
        ]);
        if ($status === 401) {
            // Token can expire; refresh once if we have username/password.
            Cache::forget($this->cacheKey());
            $token = $this->accessToken();
            if ($token === '') {
                return '';
            }
            list($status, $body) = $this->get($url, [
                'Accept: application/json',
                'Authorization: Token '.$token,
            ]);
        }
        if ($status < 200 || $status >= 300 || ! is_array($body)) {
            return '';
        }
        $name = isset($body['full_name']) ? trim((string) $body['full_name']) : '';

        return $name;
    }

    protected function accessToken()
    {
        $username = (string) config('services.campay.username');
        $password = (string) config('services.campay.password');
        if ($username !== '' && $password !== '') {
            return (string) Cache::remember($this->cacheKey(), 3300, function () use ($username, $password) {
                list($status, $body) = $this->post($this->base().'/token/', [
                    'username' => $username,
                    'password' => $password,
                ], ['Content-Type: application/json', 'Accept: application/json']);
                if ($status >= 200 && $status < 300 && is_array($body) && ! empty($body['token'])) {
                    return (string) $body['token'];
                }

                return '';
            });
        }
        $static = (string) config('services.campay.token');

        return $static;
    }

    protected function base()
    {
        $base = rtrim((string) config('services.campay.base_url'), '/');

        return $base !== '' ? $base : 'https://www.campay.net/api';
    }

    protected function cacheKey()
    {
        return 'coth:campay:access-token';
    }

    protected function get($url, array $headers)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return [$status, is_string($raw) ? json_decode($raw, true) : null];
    }

    protected function post($url, array $payload, array $headers)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return [$status, is_string($raw) ? json_decode($raw, true) : null];
    }
}
