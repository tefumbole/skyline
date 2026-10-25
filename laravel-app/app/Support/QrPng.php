<?php

namespace App\Support;

class QrPng
{
    /**
     * DNS2D renders a transparent background with no quiet zone, which phone cameras
     * often cannot read on dark screens or thermal paper. Return a solid white PNG
     * with a 4-module margin instead.
     */
    public static function make($text, $ecc = 'M', $module = 8)
    {
        $raw = base64_decode(\DNS2D::getBarcodePNG((string) $text, 'QRCODE,'.$ecc, $module, $module));
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagepng')) {
            return $raw;
        }
        $src = @imagecreatefromstring($raw);
        if (! $src) {
            return $raw;
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $margin = $module * 4;
        $out = imagecreatetruecolor($w + $margin * 2, $h + $margin * 2);
        imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
        imagecopy($out, $src, $margin, $margin, 0, 0, $w, $h);
        imagedestroy($src);

        ob_start();
        imagepng($out);
        $png = ob_get_clean();
        imagedestroy($out);

        return $png !== false && $png !== '' ? $png : $raw;
    }

    public static function dataUri($text, $ecc = 'M', $module = 8)
    {
        return 'data:image/png;base64,'.base64_encode(self::make($text, $ecc, $module));
    }
}
