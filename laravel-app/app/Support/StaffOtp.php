<?php

namespace App\Support;

class StaffOtp
{
    /**
     * Login WhatsApp codes are off for every staff account.
     * Reservations, reviews, and password reset still send their own codes.
     */
    public static function required($user)
    {
        if ($user) {
            self::markVerified($user);
        }

        return false;
    }

    /**
     * The old login pause is finished. Customer sign-in still sends its own code.
     */
    public static function paused()
    {
        return false;
    }

    protected static function markVerified($user)
    {
        if ((int) $user->otp_verify === 1) {
            return;
        }

        $user->update(['otp_verify' => 1, 'otp' => null, 'otp_time' => null]);
    }
}
