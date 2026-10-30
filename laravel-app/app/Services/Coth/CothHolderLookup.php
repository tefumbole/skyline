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
    const CAMPAY_DAILY_CALLS = 120;

    const CAMPAY_MISS_DAYS = 14;

    public function blocked()
    {
        if (Cache::has($this->blockKey())) {
            return true;
        }
        $store = $this->readHolderStore();

        return (int) $store['blocked_until'] > time();
    }

    public function name($phoneDigits)
    {
        $hit = $this->holder($phoneDigits);

        return $hit['name'];
    }

    /**
     * @return array{name:string,blocked:bool}
     */
    public function holder($phoneDigits)
    {
        $digits = $this->holderKey($phoneDigits);
        if ($digits === '') {
            return ['name' => '', 'blocked' => false];
        }
        $store = $this->readHolderStore();
        $hit = isset($store['hits'][$digits]) && is_array($store['hits'][$digits]) ? $store['hits'][$digits] : null;
        if ($hit && ! empty($hit['name'])) {
            return ['name' => (string) $hit['name'], 'blocked' => false];
        }
        $missedAt = isset($store['misses'][$digits]) ? (int) $store['misses'][$digits] : 0;
        if ($missedAt > 0 && (time() - $missedAt) < self::CAMPAY_MISS_DAYS * 86400) {
            return ['name' => '', 'blocked' => false];
        }
        if ($this->blocked()) {
            return ['name' => '', 'blocked' => true];
        }
        $today = date('Y-m-d');
        if ((string) $store['calls_on'] === $today && (int) $store['calls'] >= self::CAMPAY_DAILY_CALLS) {
            return ['name' => '', 'blocked' => true];
        }
        $token = (string) config('services.campay.token');
        if ($token === '') {
            return ['name' => '', 'blocked' => false];
        }
        $url = $this->base().'/holder_info/?phone_number='.rawurlencode($digits);
        list($status, $body) = $this->get($url, [
            'Accept: application/json',
            'Authorization: Token '.$token,
        ]);
        $this->noteHolderCall();
        if ($status === 403 || $status === 429) {
            $until = time() + 86400;
            Cache::put($this->blockKey(), $until, 86400);
            $this->mutateHolderStore(function ($store) use ($until) {
                $store['blocked_until'] = $until;

                return $store;
            });

            return ['name' => '', 'blocked' => true];
        }
        $name = $this->extractHolderName($body);
        if ($name !== '') {
            $this->mutateHolderStore(function ($store) use ($digits, $name) {
                $store['hits'][$digits] = ['name' => $name, 'address' => ''];
                unset($store['misses'][$digits]);

                return $store;
            });

            return ['name' => $name, 'blocked' => false];
        }
        if ($status === 404 || $status === 400 || $status === 200) {
            $this->mutateHolderStore(function ($store) use ($digits) {
                $store['misses'][$digits] = time();

                return $store;
            });
        }

        return ['name' => '', 'blocked' => false];
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

    protected function blockKey()
    {
        return 'campay:holder-blocked';
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
        if ($status === 403 && is_string($raw) && stripos($raw, 'failed attempts') !== false) {
            Cache::put($this->blockKey(), time() + 86400, 86400);
        }

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

    protected function holderKey($phone)
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (strpos($digits, '237') === 0) {
            $digits = substr($digits, 3);
        }
        if (strlen($digits) !== 9 || ! isset($digits[0]) || $digits[0] !== '6') {
            return '';
        }

        return '237'.$digits;
    }

    protected function extractHolderName($body)
    {
        if (! is_array($body)) {
            return '';
        }
        $bags = [$body];
        foreach (['data', 'result', 'output', 'accountHolder'] as $key) {
            if (isset($body[$key]) && is_array($body[$key])) {
                $bags[] = $body[$key];
            }
        }
        $keys = ['full_name', 'fullName', 'registeredName', 'requestedName', 'accountHolderName', 'account_holder_name', 'name'];
        foreach ($bags as $bag) {
            foreach ($keys as $key) {
                if (! isset($bag[$key]) || ! is_string($bag[$key])) {
                    continue;
                }
                $name = trim($bag[$key]);
                if ($name === '' || preg_match('/^\+?\d{8,}$/', $name)) {
                    continue;
                }

                return $name;
            }
        }

        return '';
    }

    protected function holderStorePath()
    {
        return storage_path('app/campay-holder-cache.json');
    }

    protected function emptyHolderStore()
    {
        return [
            'hits' => [],
            'misses' => [],
            'blocked_until' => 0,
            'calls_on' => '',
            'calls' => 0,
        ];
    }

    protected function readHolderStore()
    {
        $path = $this->holderStorePath();
        if (! is_file($path)) {
            return $this->emptyHolderStore();
        }
        $fp = @fopen($path, 'r');
        if (! $fp) {
            return $this->emptyHolderStore();
        }
        flock($fp, LOCK_SH);
        $raw = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded)) {
            return $this->emptyHolderStore();
        }

        return array_merge($this->emptyHolderStore(), $decoded);
    }

    protected function noteHolderCall()
    {
        $this->mutateHolderStore(function ($store) {
            $today = date('Y-m-d');
            if ((string) $store['calls_on'] !== $today) {
                $store['calls_on'] = $today;
                $store['calls'] = 0;
            }
            $store['calls'] = (int) $store['calls'] + 1;

            return $store;
        });
    }

    protected function mutateHolderStore($fn)
    {
        $path = $this->holderStorePath();
        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $fp = @fopen($path, 'c+');
        if (! $fp) {
            return null;
        }
        flock($fp, LOCK_EX);
        $raw = stream_get_contents($fp);
        $store = json_decode((string) $raw, true);
        $store = is_array($store) ? array_merge($this->emptyHolderStore(), $store) : $this->emptyHolderStore();
        $next = $fn($store);
        if (is_array($next)) {
            rewind($fp);
            ftruncate($fp, 0);
            fwrite($fp, json_encode($next));
            fflush($fp);
        }
        flock($fp, LOCK_UN);
        fclose($fp);

        return is_array($next) ? $next : $store;
    }
}
