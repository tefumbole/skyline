<?php

namespace App\Services\WhatsApp;

use App\WhatsApp\WhatsAppSetting;
use Illuminate\Support\Facades\Schema;

class WaSenderSignature
{
    public static function secret()
    {
        try {
            if (Schema::hasTable('whatsapp_settings')) {
                $saved = trim((string) WhatsAppSetting::getValue('wasender_webhook_secret', ''));
                if ($saved !== '') {
                    return $saved;
                }
            }
        } catch (\Throwable $e) {
        }

        return trim((string) config('services.whatsapp.wasender_webhook_secret'));
    }

    public static function isConfigured()
    {
        return self::secret() !== '';
    }

    public static function isValid($rawBody, $header, $secretHeader = null)
    {
        $header = trim((string) $header);
        $secretHeader = trim((string) $secretHeader);
        $secret = self::secret();
        if ($secret !== '' && self::matches($rawBody, $header, $secret)) {
            return true;
        }
        if ($secret !== '' && $secretHeader !== '' && self::same($secret, $secretHeader)) {
            return true;
        }

        return false;
    }

    /**
     * Wasender sends the live session secret in X-Webhook-Secret.
     * Keep it when that header signs the body, so a stale env secret cannot drop AI ON.
     */
    public static function learnFromWasender($ip, $rawBody, $signature, $secretHeader)
    {
        if ((string) $ip !== '2a01:4f9:c013:e3df::1') {
            return false;
        }
        $secretHeader = trim((string) $secretHeader);
        $signature = trim((string) $signature);
        if (! preg_match('/^[a-f0-9]{32}$/i', $secretHeader)) {
            return false;
        }
        if (! self::matches($rawBody, $signature, $secretHeader) && ! self::same($secretHeader, $signature)) {
            return false;
        }
        $current = self::secret();
        if ($current !== '' && self::same($current, $secretHeader)) {
            return true;
        }
        if (! Schema::hasTable('whatsapp_settings')) {
            return true;
        }
        WhatsAppSetting::putValue('wasender_webhook_secret', $secretHeader);

        return true;
    }

    protected static function matches($rawBody, $header, $secret)
    {
        if ($header === '' || $secret === '') {
            return false;
        }
        if (self::same($secret, $header)) {
            return true;
        }
        $hmac = hash_hmac('sha256', (string) $rawBody, $secret);
        if (self::same($hmac, $header)) {
            return true;
        }
        if (stripos($header, 'sha256=') === 0) {
            return self::same($hmac, substr($header, 7));
        }
        $raw = hash_hmac('sha256', (string) $rawBody, $secret, true);

        return self::same($raw, $header);
    }

    protected static function same($known, $given)
    {
        if (! is_string($known) || ! is_string($given) || strlen($known) !== strlen($given)) {
            return false;
        }

        return hash_equals($known, $given);
    }
}
