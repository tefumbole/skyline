<?php

namespace App\Services;

use App\Support\WhatsAppPhone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class SystemTestOtp
{
    public function send($phone)
    {
        $normalized = WhatsAppPhone::normalize($phone);
        $countKey = 'system-test-otp-count:'.$normalized;
        $count = (int) Cache::get($countKey, 0);
        if ($count >= 5) {
            throw new \RuntimeException('Too many codes were sent to this phone. Wait and try again.');
        }
        $sentKey = 'system-test-otp-sent:'.$normalized;
        $sentAt = (int) Cache::get($sentKey, 0);
        $elapsed = time() - $sentAt;
        if ($sentAt && $elapsed < 60) {
            throw new \RuntimeException('You can request another code in '.(60 - $elapsed).' seconds.');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($this->key($normalized), [
            'hash' => Hash::make($code),
            'attempts' => 0,
        ], now()->addMinutes(10));
        Cache::put($countKey, $count + 1, now()->addHour());
        Cache::put($sentKey, time(), now()->addMinutes(10));

        $result = app(\App\Services\Messaging\NotificationRouter::class)
            ->sendWhatsAppOtp($normalized, $code, 'site_test', 10);
        if (empty($result['success'])) {
            Cache::forget($this->key($normalized));
            Cache::forget($sentKey);
            $error = isset($result['error']) && $result['error'] !== ''
                ? (string) $result['error']
                : 'We could not send the verification code on WhatsApp.';
            throw new \RuntimeException($error);
        }
    }

    public function verify($phone, $code)
    {
        $normalized = WhatsAppPhone::normalize($phone);
        $row = Cache::get($this->key($normalized));
        if (! is_array($row) || empty($row['hash'])) {
            throw new \RuntimeException('Request a new code.');
        }
        $attempts = isset($row['attempts']) ? (int) $row['attempts'] : 0;
        if ($attempts >= 5) {
            Cache::forget($this->key($normalized));
            throw new \RuntimeException('That code is no longer valid. Request a new one.');
        }
        $given = preg_replace('/\D/', '', (string) $code);
        if (strlen($given) !== 6 || ! Hash::check($given, $row['hash'])) {
            $row['attempts'] = $attempts + 1;
            Cache::put($this->key($normalized), $row, now()->addMinutes(10));
            throw new \RuntimeException('That code does not match.');
        }
        Cache::forget($this->key($normalized));

        return $normalized;
    }

    protected function key($phone)
    {
        return 'system-test-otp:'.$phone;
    }
}
