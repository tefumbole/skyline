<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Gallery cards are shown about 400px wide, and the zoom view fills the screen.
 * A card file at 800px and a zoom file inside 1600px stay sharp on a phone screen
 * while a phone-camera original (often several megabytes) does not have to download.
 */
class GalleryImage
{
    const FULL = 1600;

    const CARD = 800;

    const FULL_MAX_BYTES = 430080; // ~420KB

    const CARD_MAX_BYTES = 143360; // ~140KB

    /**
     * @return array{file:string,thumb:string}|null
     */
    public static function storeUpload(UploadedFile $file, $dir)
    {
        $tmp = $file->getRealPath();
        if (! $tmp || ! is_file($tmp)) {
            return null;
        }
        $base = 'gallery_image_'.time().'_'.bin2hex(random_bytes(4));

        return self::writePair($tmp, $dir, $base);
    }

    /**
     * Rewrite an existing photo. Returns null when the file cannot be read.
     *
     * @return array{file:string,thumb:string}|null
     */
    public static function optimizeExisting($absolutePath, $dir)
    {
        if (! is_file($absolutePath)) {
            return null;
        }
        $base = pathinfo($absolutePath, PATHINFO_FILENAME);
        if (substr($base, -5) === '_card') {
            return null;
        }
        $pair = self::writePair($absolutePath, $dir, $base.'_opt');
        if (! $pair) {
            return null;
        }
        $full = $dir.DIRECTORY_SEPARATOR.basename($pair['file']);
        if (is_file($absolutePath) && realpath($absolutePath) !== realpath($full)) {
            @unlink($absolutePath);
        }

        return $pair;
    }

    public static function alreadyFitted($fullPath, $thumbPath)
    {
        $full = self::dimensions($fullPath);
        $thumb = self::dimensions($thumbPath);
        if (! $full || ! $thumb) {
            return false;
        }

        return $full['w'] <= self::FULL && $full['h'] <= self::FULL
            && $thumb['w'] <= self::CARD && $thumb['h'] <= self::CARD
            && self::isJpeg($fullPath) && self::isJpeg($thumbPath)
            && filesize($fullPath) > 0 && filesize($fullPath) <= self::FULL_MAX_BYTES
            && filesize($thumbPath) > 0 && filesize($thumbPath) <= self::CARD_MAX_BYTES;
    }

    /**
     * @return array{file:string,thumb:string}|null
     */
    public static function writePair($source, $dir, $base)
    {
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return null;
        }
        $loaded = self::load($source);
        if (! $loaded) {
            return null;
        }

        $fullName = $base.'.jpg';
        $cardName = $base.'_card.jpg';
        $fullDest = rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$fullName;
        $cardDest = rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$cardName;

        $fitsOnCard = $loaded['w'] <= self::CARD && $loaded['h'] <= self::CARD;
        $full = self::fit($loaded['im'], $loaded['w'], $loaded['h'], self::FULL, self::FULL);
        $card = $fitsOnCard ? null : self::fit($loaded['im'], $loaded['w'], $loaded['h'], self::CARD, self::CARD);
        imagedestroy($loaded['im']);

        $okFull = $full && self::saveJpeg($full, $fullDest, self::FULL_MAX_BYTES);
        $okCard = $fitsOnCard || ($card && self::saveJpeg($card, $cardDest, self::CARD_MAX_BYTES));
        if ($full) {
            imagedestroy($full);
        }
        if ($card) {
            imagedestroy($card);
        }
        if (! $okFull || ! $okCard || ! is_file($fullDest) || (! $fitsOnCard && ! is_file($cardDest))) {
            @unlink($fullDest);
            @unlink($cardDest);

            return null;
        }

        $relative = self::relativeDir($dir);

        return [
            'file' => $relative.'/'.$fullName,
            'thumb' => $fitsOnCard ? $relative.'/'.$fullName : $relative.'/'.$cardName,
        ];
    }

    public static function deleteStored($path)
    {
        $absolute = self::storedFile($path);
        if ($absolute && is_file($absolute)) {
            @unlink($absolute);
        }
    }

    /**
     * Drop image files left behind after a replacement or a removed gallery item.
     */
    public static function removeUnreferenced(array $relativeDirs, array $keep)
    {
        $keep = array_fill_keys($keep, true);
        $removed = 0;
        foreach ($relativeDirs as $relative) {
            $dir = public_path($relative);
            if (! is_dir($dir)) {
                continue;
            }
            foreach (scandir($dir) as $name) {
                if ($name === '.' || $name === '..') {
                    continue;
                }
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                    continue;
                }
                $stored = trim($relative, '/').'/'.$name;
                if (isset($keep[$stored])) {
                    continue;
                }
                $path = $dir.DIRECTORY_SEPARATOR.$name;
                if (is_file($path) && @unlink($path)) {
                    $removed++;
                }
            }
        }

        return $removed;
    }

    protected static function storedFile($path)
    {
        if (! $path) {
            return null;
        }
        $relative = ltrim(str_replace('\\', '/', $path), '/');
        if (! preg_match('#^images/(gallery|menu)/[^/]+$#', $relative)) {
            return null;
        }

        return public_path($relative);
    }

    protected static function relativeDir($dir)
    {
        $public = rtrim(str_replace('\\', '/', public_path()), '/');
        $dirNorm = rtrim(str_replace('\\', '/', $dir), '/');
        if (strpos($dirNorm, $public.'/') === 0) {
            return ltrim(substr($dirNorm, strlen($public)), '/');
        }

        return 'images/gallery';
    }

    protected static function load($path)
    {
        if (! function_exists('imagecreatetruecolor') || ! is_file($path)) {
            return null;
        }
        $size = @getimagesize($path);
        if (! $size || empty($size[0]) || empty($size[1])) {
            return null;
        }
        switch ($size[2]) {
            case IMAGETYPE_JPEG:
                $im = @imagecreatefromjpeg($path);
                break;
            case IMAGETYPE_PNG:
                $im = @imagecreatefrompng($path);
                break;
            case IMAGETYPE_GIF:
                $im = @imagecreatefromgif($path);
                break;
            case IMAGETYPE_WEBP:
                $im = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false;
                break;
            default:
                $im = false;
        }
        if (! $im) {
            return null;
        }
        if ($size[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);
            $orientation = (int) (isset($exif['Orientation']) ? $exif['Orientation'] : 0);
            $angle = $orientation === 3 ? 180 : ($orientation === 6 ? -90 : ($orientation === 8 ? 90 : 0));
            if ($angle) {
                $rotated = @imagerotate($im, $angle, 0);
                if ($rotated) {
                    imagedestroy($im);
                    $im = $rotated;
                }
            }
        }

        return ['im' => $im, 'w' => imagesx($im), 'h' => imagesy($im)];
    }

    protected static function fit($src, $srcW, $srcH, $maxW, $maxH)
    {
        $scale = min($maxW / max(1, $srcW), $maxH / max(1, $srcH), 1);
        $dstW = max(1, (int) round($srcW * $scale));
        $dstH = max(1, (int) round($srcH * $scale));
        $dst = imagecreatetruecolor($dstW, $dstH);
        if (! $dst) {
            return null;
        }
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        return $dst;
    }

    /**
     * Start near visually lossless and only step down while the file is still heavy.
     */
    protected static function saveJpeg($image, $path, $maxBytes)
    {
        if (function_exists('imageinterlace')) {
            imageinterlace($image, 1);
        }
        $written = false;
        foreach ([84, 78, 74] as $quality) {
            $tmp = $path.'.tmp'.mt_rand(1000, 9999);
            $ok = @imagejpeg($image, $tmp, $quality);
            if (! $ok || ! is_file($tmp) || filesize($tmp) < 1) {
                @unlink($tmp);
                continue;
            }
            if (! @rename($tmp, $path)) {
                @unlink($tmp);
                continue;
            }
            @chmod($path, 0644);
            $written = true;
            if (filesize($path) <= $maxBytes) {
                return true;
            }
        }

        return $written;
    }

    protected static function dimensions($path)
    {
        if (! $path || ! is_file($path)) {
            return null;
        }
        $size = @getimagesize($path);
        if (! $size || empty($size[0]) || empty($size[1])) {
            return null;
        }

        return ['w' => (int) $size[0], 'h' => (int) $size[1]];
    }

    protected static function isJpeg($path)
    {
        $size = @getimagesize($path);

        return $size && isset($size[2]) && $size[2] === IMAGETYPE_JPEG;
    }
}
