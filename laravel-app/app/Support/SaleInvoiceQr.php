<?php

namespace App\Support;

use App\GeneralSetting;
use App\Sale;

class SaleInvoiceQr
{
    public static function token(Sale $sale): string
    {
        return substr(hash_hmac(
            'sha256',
            'sale-invoice|'.$sale->id.'|'.$sale->reference_no,
            (string) config('app.key')
        ), 0, 20);
    }

    public static function scanUrl(Sale $sale): string
    {
        return url('/verify/invoice/'.$sale->id.'/'.self::token($sale));
    }

    public static function isValid(Sale $sale, string $token): bool
    {
        return hash_equals(self::token($sale), (string) $token);
    }

    public static function dataUri(Sale $sale, $withLogo = false)
    {
        return 'data:image/png;base64,'.base64_encode(self::png($sale, $withLogo));
    }

    public static function png(Sale $sale, $withLogo = false)
    {
        $raw = QrPng::make(self::scanUrl($sale), $withLogo ? 'H' : 'M', 8);
        if (! $withLogo || ! function_exists('imagecreatefromstring') || ! function_exists('imagepng')) {
            return $raw;
        }
        $qr = @imagecreatefromstring($raw);
        $logo = $qr ? self::logoImage() : null;
        if (! $qr || ! $logo) {
            if ($qr) {
                imagedestroy($qr);
            }

            return $raw;
        }

        $qw = imagesx($qr);
        $qh = imagesy($qr);
        $logoSize = max(24, (int) round($qw * 0.22));
        $pad = max(3, (int) round($logoSize * 0.16));
        $box = $logoSize + ($pad * 2);
        $x = (int) (($qw - $box) / 2);
        $y = (int) (($qh - $box) / 2);
        $white = imagecolorallocate($qr, 255, 255, 255);
        imagefilledrectangle($qr, $x, $y, $x + $box - 1, $y + $box - 1, $white);

        $scaled = imagecreatetruecolor($logoSize, $logoSize);
        $bg = imagecolorallocate($scaled, 255, 255, 255);
        imagefilledrectangle($scaled, 0, 0, $logoSize, $logoSize, $bg);
        imagecopyresampled($scaled, $logo, 0, 0, 0, 0, $logoSize, $logoSize, imagesx($logo), imagesy($logo));
        imagecopy($qr, $scaled, $x + $pad, $y + $pad, 0, 0, $logoSize, $logoSize);
        imagedestroy($scaled);
        imagedestroy($logo);

        ob_start();
        imagepng($qr);
        $out = ob_get_clean();
        imagedestroy($qr);

        return $out !== false && $out !== '' ? $out : $raw;
    }

    protected static function logoImage()
    {
        $setting = GeneralSetting::query()->first();
        $file = $setting && $setting->site_logo ? basename((string) $setting->site_logo) : '';
        $path = $file !== '' ? base_path('public/logo/'.$file) : '';
        if ($path === '' || ! is_file($path)) {
            return null;
        }
        $info = @getimagesize($path);
        if (! $info) {
            return null;
        }
        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                return function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : null;
            case IMAGETYPE_PNG:
                return function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : null;
            case IMAGETYPE_GIF:
                return function_exists('imagecreatefromgif') ? @imagecreatefromgif($path) : null;
            case IMAGETYPE_WEBP:
                return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null;
            default:
                return null;
        }
    }
}
