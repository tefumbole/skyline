---
name: campay-holder-lookup
description: >-
  Resolve a Cameroon MTN/Orange Mobile Money phone number into its registered
  holder name using the Campay `holder_info` endpoint. PHP/Laravel ready-to-port
  implementation that falls back to PawaPay when Campay has no record. Use when
  the user asks to add Campay name lookup, port the BeyondTechWorld / Beyond
  Enterprise MoMo-name feature to another site (Skyline, AlphaBridge, Manukeza,
  New Vision, etc.), reconcile a phone list into real customer names, or recall
  the Campay `/holder_info/?phone_number=` call pattern.
---

# Campay holder-info name lookup

Resolves a Cameroon mobile-money number into the name registered against that
MTN or Orange MoMo account. This is the implementation that powers the Campay
name resolution in BeyondTechWorld (customer imports, Payout name hints, deposit
client lookup). The source code is in [reference.md](reference.md) — copy it
verbatim into the target Laravel app.

## What this provides

- `MobileMoneyHolderService::lookup($phone)` returns
  `['name' => ?string, 'address' => ?string, 'source' => 'campay'|'pawapay'|null]`.
- Campay first, PawaPay fallback (only when a PawaPay token is set).
- Robust name extraction (handles `full_name`, `fullName`, `registeredName`,
  `requestedName`, `accountHolderName`, nested under `data` / `result` /
  `output` / `accountHolder`).
- Uses raw cURL — no Guzzle dependency.
- Logs failures through Laravel's `Log` facade, scrubbing the phone number from
  the URL.

## Campay facts (do not guess)

- Base URL: `https://www.campay.net/api` (configurable via `CAMPAY_BASE_URL`).
- Endpoint: `GET /holder_info/?phone_number=<9-digit-local or 237xxxxxxxxx>`.
- Auth header: `Authorization: Token <CAMPAY_TOKEN>` (static Permanent App Token,
  NOT `Bearer`, NOT basic). Static tokens come from Campay dashboard → App
  Settings → Permanent Access Token.
- Response is JSON, 200 on success. 404 means the number is not registered.
  Non-200 responses are logged at `info`.
- Numbers must be digits only; the service strips `+`/spaces before calling.
- Rate limits are low; batch loops should use 4–8 concurrent requests at most
  and retry on HTTP 0/429/5xx.

## Setup in the target Laravel app

1. Copy `app/Services/MobileMoneyHolderService.php` from
   [reference.md](reference.md).
2. Add the `campay` block to `config/services.php`:

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

3. Add `.env` keys on the server (not committed):

   ```
   CAMPAY_TOKEN=
   CAMPAY_BASE_URL=https://www.campay.net/api
   # optional, only for non-Cameroon fallback:
   PAWAPAY_API_TOKEN=
   PAWAPAY_ENVIRONMENT=production
   ```

   The token has to live on the server where PHP-FPM / the queue reads the
   `.env`. Developers running locally without a token get `['name' => null,
   'source' => null]` and should not treat that as a bug.

4. After editing `.env`, clear the config cache and reload PHP-FPM:

   ```bash
   sudo -u www-data php artisan config:clear
   sudo systemctl reload php7.4-fpm   # or php8.x-fpm, matching the server
   ```

## Quick usage

```php
$holder = app(\App\Services\MobileMoneyHolderService::class)->lookup('237670703582');

if ($holder['source'] === 'campay' && ! empty($holder['name'])) {
    $name = $holder['name']; // registered MoMo account holder
}
```

## Common failure modes and the fix

| Symptom | Reason | Fix |
|---|---|---|
| `['name' => null, 'source' => null]` for every number | `CAMPAY_TOKEN` is empty in the env the app loaded | Put the token in `.env`, `config:clear`, reload PHP-FPM; make sure PHP-FPM runs as the same user that reads the env (www-data) |
| Works from CLI as root, fails from the browser | Running `php artisan config:cache` or `php artisan` as root wrote cache files owned by root that `www-data` cannot read | `chown -R www-data:www-data storage bootstrap/cache` and `php artisan config:clear` as `www-data` |
| 401 on `holder_info/` | Wrong header style | Must be literal `Authorization: Token <token>` — not `Bearer`, not `Basic`, no space before the token |
| 404 / `null` for a specific number | Number is not registered as MTN/Orange MoMo | Keep the raw number; do NOT invent a name |
| Timeout on bulk runs | Running too many concurrent lookups | Cap at ~8 in-flight; retry HTTP 0 / 429 / 5xx twice with 1–2 s back-off |

## Phone shape

Campay accepts both `237670703582` and `670703582`. The service passes digits
through unchanged (after stripping non-digits) and lets Campay normalize. When
porting into a bulk importer, keep the full `237…` form so a cached map on your
side does not collide across countries.

Pair this skill with a Campay payout service (`collect` / `withdraw`) if the
target app also needs to send or receive money through MTN/Orange MoMo.
