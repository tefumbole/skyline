<?php

namespace App;

use App\Traits\NormalizesWhatsAppPhones;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable;
    use HasRoles;
    use NormalizesWhatsAppPhones;

    protected $whatsappPhoneAttributes = ['phone', 'additional_phone'];

    protected $fillable = [
        'name', 'username', 'email', 'password', 'phone', 'additional_phone', 'company_name', 'role_id', 'biller_id', 'warehouse_id', 'is_active', 'is_deleted', 'sign', 'stemp', 'approve', 'sign_request_token', 'sign_request_type', 'sign_request_expires_at', 'otp', 'otp_time', 'otp_verify', 'otp_disabled', 'must_set_password',
    ];

    protected $casts = [
        'must_set_password' => 'boolean',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $dates = [
        'sign_request_expires_at',
        'otp_time',
    ];

    protected static $roleNames = [];

    public function isActive()
    {
        return $this->is_active;
    }

    public function roleName()
    {
        $id = (int) $this->role_id;
        if (! isset(self::$roleNames[$id])) {
            self::$roleNames[$id] = (string) \Illuminate\Support\Facades\DB::table('roles')->where('id', $id)->value('name');
        }

        return self::$roleNames[$id];
    }

    public function isSuperAdmin()
    {
        return $this->roleName() === 'Super Admin';
    }

    /** Shop buyer. Role id 5 is Restaurant, so the name is the check. */
    public function isShopCustomer()
    {
        return strcasecmp($this->roleName(), 'Customer') === 0;
    }

    public function isAssetStaff()
    {
        $name = strtolower($this->roleName());

        return $name === 'asset' || $name === 'assets' || $name === 'asset manager';
    }

    /** Kitchen, bar, and floor staff. Their home is the order queue. */
    public function isStationStaff()
    {
        $name = strtolower($this->roleName());

        return in_array($name, ['restaurant', 'bar', 'waiter'], true);
    }

    /**
     * Hotel administrator (role 1) or Super Admin. Owner tools such as
     * WhatsApp Hub stay on Super Admin only.
     */
    public function hasFullAdminAccess()
    {
        return (int) $this->role_id === 1 || $this->isSuperAdmin();
    }

    /** Sending money out. Viewing deposits and payouts is separate. */
    public function canSendPayout()
    {
        if ($this->hasFullAdminAccess()) {
            return true;
        }
        try {
            $role = \Spatie\Permission\Models\Role::find($this->role_id);

            return $role && $role->hasPermissionTo('payments-payout');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Settings stays with administrators. Station roles use My Profile instead. */
    public function canManageHotelSettings()
    {
        return $this->hasFullAdminAccess() || strcasecmp($this->roleName(), 'Admin') === 0;
    }

    /** First usable WhatsApp number on the staff profile. */
    public function whatsappPhone()
    {
        foreach (['phone', 'additional_phone'] as $attr) {
            $value = trim((string) $this->{$attr});
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    public function holiday() {
        return $this->hasMany('App\Holiday');
    }

    public function order() {
        return $this->hasMany('App\Order');
    }

    public function customer() {
        return $this->hasOne('App\Customer', 'user_id', 'id');
    }
}
