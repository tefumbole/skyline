<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class StaffOtp
{
    /**
     * True when this login must enter a WhatsApp code.
     * otp_disabled is a per-account pause; the role permission stays in place.
     */
    public static function required($user)
    {
        if (! $user || LocalDevAuth::skipStaffOtp()) {
            return false;
        }
        if (Schema::hasColumn($user->getTable(), 'otp_disabled') && (int) $user->otp_disabled === 1) {
            return false;
        }

        $role = Role::find($user->role_id);
        if (! $role || (int) $role->id === 5) {
            return false;
        }

        try {
            return (bool) $role->hasPermissionTo('one_time_otp');
        } catch (\Throwable $e) {
            return false;
        }
    }
}
