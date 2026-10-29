<?php

namespace App\Support;

use App\Notifications\MoneyNotice;
use App\User;

class MoneyAlerts
{
    public static function staff($message)
    {
        $notice = new MoneyNotice($message);
        $users = User::where('is_active', 1)->whereIn('role_id', [1, 4, 8])->get();
        foreach ($users as $user) {
            try {
                $user->notify($notice);
            } catch (\Throwable $e) {
                \Log::warning('money alert: '.$e->getMessage());
            }
        }
    }

    public static function whatsapp($phone, $message)
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return;
        }
        try {
            app(\App\Services\Messaging\NotificationRouter::class)->sendWhatsAppText($phone, $message);
        } catch (\Throwable $e) {
            \Log::warning('money whatsapp: '.$e->getMessage());
        }
    }
}
