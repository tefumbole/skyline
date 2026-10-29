# Campay holder-info lookup — reference source

Verbatim implementation that powers the Campay MoMo-name resolution in
BeyondTechWorld. Copy this file into the target Laravel app at
`app/Services/MobileMoneyHolderService.php` and wire the `campay` and
`pawapay` config blocks from `SKILL.md`.

## `app/Services/MobileMoneyHolderService.php`

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * MoMo account-holder name lookup.
 * Campay covers MTN/Orange in Cameroon; PawaPay covers other networks when
 * a merchant token is set.
 */
class MobileMoneyHolderService
{
    /**
     * @return array{name:?string,address:?string,source:?string}
     */
    public function lookup($phone)
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (strlen($digits) < 8) {
            return ['name' => null, 'address' => null, 'source' => null];
        }

        $hit = $this->campay($digits);
        if ($hit) {
            return $hit;
        }

        return $this->pawapay($digits) ?: ['name' => null, 'address' => null, 'source' => null];
    }

    protected function campay($digits)
    {
        $token = config('services.campay.token');
        if (! $token) {
            $token = getenv('CAMPAY_TOKEN') ?: getenv('MOMO_TOKEN');
        }
        if (! $token) {
            return null;
        }

        $base = rtrim((string) config('services.campay.base_url', 'https://www.campay.net/api'), '/');
        $url = $base.'/holder_info/?phone_number='.urlencode($digits);
        $body = $this->httpGet($url, [
            'Authorization: Token '.$token,
            'Content-Type: application/json',
            'Accept: application/json',
        ], 12);
        $name = $this->extractName($body);
        $address = $this->extractAddress($body);
        if ($name || $address) {
            return ['name' => $name, 'address' => $address, 'source' => 'campay'];
        }

        return null;
    }

    protected function pawapay($digits)
    {
        $token = config('services.pawapay.api_token') ?: getenv('PAWAPAY_API_TOKEN') ?: getenv('PAWAPAY_TOKEN');
        if (! $token) {
            Log::info('PawaPay name lookup skipped: PAWAPAY_API_TOKEN is empty');

            return null;
        }

        $env = strtolower((string) (config('services.pawapay.environment') ?: getenv('PAWAPAY_ENVIRONMENT') ?: 'production'));
        $base = ($env === 'sandbox')
            ? 'https://api.sandbox.pawapay.io'
            : rtrim((string) (config('services.pawapay.live_base_url') ?: 'https://api.pawapay.io'), '/');

        $msisdn = ltrim($digits, '0');
        $headers = [
            'Authorization: Bearer '.$token,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $attempts = [
            [$base.'/v2/name-lookups', json_encode(['phoneNumber' => $msisdn])],
            [$base.'/v2/name-lookups', json_encode(['phoneNumber' => '+'.$msisdn])],
            [$base.'/v1/standard/name-lookup', json_encode([
                'depositId' => (string) Str::uuid(),
                'payer' => [
                    'type' => 'MSISDN',
                    'address' => ['value' => $msisdn],
                ],
            ])],
        ];

        foreach ($attempts as $pair) {
            $body = $this->httpPost($pair[0], $headers, $pair[1], 15);
            $name = $this->extractName($body);
            $address = $this->extractAddress($body);
            if ($name || $address) {
                return ['name' => $name, 'address' => $address, 'source' => 'pawapay'];
            }
        }

        return null;
    }

    protected function extractName($decoded)
    {
        if (! is_array($decoded)) {
            return null;
        }
        $nested = [];
        foreach (['data', 'result', 'output', 'name', 'accountHolder'] as $key) {
            if (isset($decoded[$key]) && is_array($decoded[$key])) {
                $nested[] = $decoded[$key];
            }
        }
        $bags = array_merge([$decoded], $nested);
        $keys = [
            'full_name', 'fullName', 'registeredName', 'requestedName',
            'accountHolderName', 'account_holder_name', 'financialAddressName', 'name',
        ];
        foreach ($bags as $bag) {
            foreach ($keys as $key) {
                $raw = $bag[$key] ?? null;
                if (is_array($raw)) {
                    $raw = $raw['fullName'] ?? $raw['full_name'] ?? $raw['registeredName'] ?? null;
                }
                if (! is_string($raw)) {
                    continue;
                }
                $name = trim($raw);
                if ($name === '' || preg_match('/^\+?\d{8,}$/', $name)) {
                    continue;
                }

                return $name;
            }
        }

        return null;
    }

    protected function extractAddress($decoded)
    {
        if (! is_array($decoded)) {
            return null;
        }
        $nested = [];
        foreach (['data', 'result', 'output', 'accountHolder'] as $key) {
            if (isset($decoded[$key]) && is_array($decoded[$key])) {
                $nested[] = $decoded[$key];
            }
        }
        $bags = array_merge([$decoded], $nested);
        $keys = ['address', 'full_address', 'fullAddress', 'location', 'town', 'city', 'quartier', 'region'];
        $parts = [];
        foreach ($bags as $bag) {
            foreach ($keys as $key) {
                $raw = $bag[$key] ?? null;
                if (! is_string($raw)) {
                    continue;
                }
                $val = trim($raw);
                if ($val === '' || strtoupper($val) === 'N/A' || strtoupper($val) === 'NAN' || preg_match('/^\+?\d{8,}$/', $val)) {
                    continue;
                }
                if (! in_array($val, $parts, true)) {
                    $parts[] = $val;
                }
            }
            if ($parts) {
                break;
            }
        }

        return $parts ? implode(', ', $parts) : null;
    }

    protected function httpGet($url, array $headers, $timeout)
    {
        return $this->curl('GET', $url, $headers, null, $timeout);
    }

    protected function httpPost($url, array $headers, $payload, $timeout)
    {
        return $this->curl('POST', $url, $headers, $payload, $timeout);
    }

    protected function curl($method, $url, array $headers, $payload, $timeout)
    {
        $curl = curl_init();
        $opts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => (int) $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
        ];
        if ($payload !== null) {
            $opts[CURLOPT_POSTFIELDS] = $payload;
        }
        curl_setopt_array($curl, $opts);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($err) {
            Log::info('MoMo holder lookup failed: '.$err);

            return null;
        }
        if ($code >= 400) {
            Log::info('MoMo holder lookup HTTP '.$code, ['url' => preg_replace('/phone_number=[^&]*/', 'phone_number=***', $url)]);
        }

        $decoded = json_decode((string) $response, true);

        return is_array($decoded) ? $decoded : null;
    }
}
```

## `config/services.php` block

```php
'campay' => [
    'token' => env('CAMPAY_TOKEN', env('MOMO_TOKEN')),
    'base_url' => env('CAMPAY_BASE_URL', 'https://www.campay.net/api'),
],

'pawapay' => [
    'api_token' => env('PAWAPAY_API_TOKEN', env('PAWAPAY_TOKEN')),
    'environment' => env('PAWAPAY_ENVIRONMENT', 'production'),
    'live_base_url' => env('PAWAPAY_LIVE_BASE_URL', 'https://api.pawapay.io'),
],
```

## `.env` keys

```
CAMPAY_TOKEN=
CAMPAY_BASE_URL=https://www.campay.net/api
PAWAPAY_API_TOKEN=
PAWAPAY_ENVIRONMENT=production
```

## Smoke test on the server

```bash
sudo -u www-data php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$hit = app(App\Services\MobileMoneyHolderService::class)->lookup("237670703582");
var_dump($hit);
'
```

A healthy run prints:

```
array(3) {
  ["name"]=> string(…) "SOMEBODY NAME"
  ["address"]=> NULL
  ["source"]=> string(6) "campay"
}
```

If `source` is `null`, re-check the `.env` token, run
`sudo -u www-data php artisan config:clear`, and make sure `storage/` and
`bootstrap/cache/` are owned by `www-data:www-data`.

## Bulk importer pattern (used in BeyondTechWorld)

- Read the input list of phone numbers (strip non-digits).
- De-duplicate and resolve each unique number once.
- Cap concurrency at 4–8 in-flight requests (process in chunks, not one giant
  parallel burst).
- Retry on `source === null` where the response had a cURL error or HTTP 0 /
  429 / 5xx — two retries, 1 and 2 seconds apart.
- Only accept a Campay name when `source === 'campay'` and `name !== ''`.
- Never invent a name for the leftovers; keep the raw number so the admin can
  resolve it later.
