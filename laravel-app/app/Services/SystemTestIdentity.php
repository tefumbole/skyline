<?php

namespace App\Services;

use App\Support\WhatsAppPhone;
use App\User;
use Illuminate\Support\Facades\Schema;

class SystemTestIdentity
{
    public function resolve($raw)
    {
        try {
            $phone = WhatsAppPhone::normalize($raw);
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException('Enter a valid WhatsApp number. A Cameroon number can be 675321739.');
        }

        $system = $this->systemName($phone);
        if ($system !== '') {
            return ['phone' => $phone, 'name' => $system, 'source' => 'system'];
        }

        $campay = $this->campayName($phone);
        if ($campay !== '') {
            return ['phone' => $phone, 'name' => $campay, 'source' => 'campay'];
        }

        return ['phone' => $phone, 'name' => '', 'source' => 'manual'];
    }

    protected function systemName($phone)
    {
        $customer = app(PeopleDirectoryService::class)->findCustomerByLoosePhone($phone);
        if ($customer && trim((string) $customer->name) !== '') {
            return trim((string) $customer->name);
        }

        return $this->userName($phone);
    }

    protected function userName($phone)
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'phone')) {
            return '';
        }

        $user = User::where('phone', $phone)->orWhere('phone', '+'.$phone)->first();
        if (! $user && Schema::hasColumn('users', 'additional_phone')) {
            $user = User::where('additional_phone', $phone)->orWhere('additional_phone', '+'.$phone)->first();
        }
        if (! $user) {
            $tail = substr(preg_replace('/\D/', '', $phone), -9);
            if (strlen($tail) >= 8) {
                try {
                    $user = User::whereRaw(
                        "RIGHT(REPLACE(REPLACE(REPLACE(COALESCE(phone,''), '+', ''), ' ', ''), '-', ''), 9) = ?",
                        [$tail]
                    )->first();
                } catch (\Throwable $e) {
                    $user = null;
                }
            }
        }
        if (! $user) {
            return '';
        }

        return trim((string) $user->name);
    }

    protected function campayName($phone)
    {
        try {
            $hit = app(MobileMoneyHolderService::class)->lookup($phone);
        } catch (\Throwable $e) {
            return '';
        }
        if (! is_array($hit) || ($hit['source'] ?? '') !== 'campay') {
            return '';
        }

        return trim((string) ($hit['name'] ?? ''));
    }
}
