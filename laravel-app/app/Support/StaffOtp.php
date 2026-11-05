<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class StaffOtp
{
    /**
     * WhatsApp login codes stay off until this UTC instant, then required()
     * turns itself back on. 48 hours from 5 Nov 2026, 20:53 (UTC+2).
     */
    const BYPASS_UNTIL = '2026-11-07 18:53:00 UTC';

    /**
     * True when this login must enter a WhatsApp code.
     * otp_disabled is a per-account pause; the role permission stays in place.
     */
    public static function required($user)
    {
        if (! $user || LocalDevAuth::skipStaffOtp()) {
            return false;
        }
        if (self::paused()) {
            self::markVerified($user);

            return false;
        }
        if (Schema::hasColumn($user->getTable(), 'otp_disabled') && (int) $user->otp_disabled === 1) {
            return false;
        }

        $role = Role::find($user->role_id);
        if (! $role) {
            return false;
        }

        try {
            return (bool) $role->hasPermissionTo('one_time_otp');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Temporary WhatsApp outage window. Password and the login QR still apply.
     */
    public static function paused()
    {
        return time() < strtotime(self::BYPASS_UNTIL);
    }

    protected static function markVerified($user)
    {
        if ((int) $user->otp_verify === 1) {
            return;
        }

        $user->update(['otp_verify' => 1, 'otp' => null, 'otp_time' => null]);
    }
}
