<?php

namespace App\Support;

use App\User;

class LoginQr
{
    /**
     * Stable code for this staff account. The login page shows it as a QR
     * and the person must type it before the session starts.
     */
    public static function code(User $user): string
    {
        return strtoupper(substr(hash_hmac(
            'sha256',
            'skyline-login-qr|'.$user->id,
            (string) config('app.key')
        ), 0, 8));
    }

    public static function matches(User $user, $input): bool
    {
        $given = strtoupper(preg_replace('/[^A-F0-9]/', '', (string) $input));

        return $given !== '' && hash_equals(self::code($user), $given);
    }

    public static function imageDataUri(User $user): string
    {
        return 'data:image/png;base64,'.base64_encode(QrPng::make(self::code($user), 'M', 6));
    }
}
