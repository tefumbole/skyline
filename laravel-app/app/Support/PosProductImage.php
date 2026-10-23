<?php

namespace App\Support;

class PosProductImage
{
    // POS tiles draw the photo at about 70–120px. 240px is 2× that, so it stays sharp
    // on a retina screen and a JPEG stays well under 45KB.
    const MAX_BYTES = 17408;
    const THUMB_EDGE = 240;
    const ORIGINAL_EDGE = 240;

    public static function url($filename, $ensureThumb = false)
    {
        $filename = self::firstName($filename);
        if ($ensureThumb) {
            self::ensureThumb($filename);
        }
        $thumb = public_path('images/product/thumbs/'.$filename);
        if (is_file($thumb) && filesize($thumb) > 0) {
            return self::assetFile('images/product/thumbs/'.$filename);
        }
        $orig = public_path('images/product/'.$filename);
        if ($filename !== 'zummXD2dvAtI.png' && is_file($orig) && filesize($orig) > 0) {
            return self::assetFile('images/product/'.$filename);
        }
        $placeholder = public_path('images/product/zummXD2dvAtI.png');
        if (is_file($placeholder)) {
            return self::assetFile('images/product/zummXD2dvAtI.png');
        }

        return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';
    }

    public static function catalogUrl($subdir, $filename)
    {
        $filename = self::firstName($filename);
        $path = public_path('images/'.$subdir.'/'.$filename);
        if ($filename !== 'zummXD2dvAtI.png' && is_file($path) && filesize($path) > 0) {
            return self::assetFile('images/'.$subdir.'/'.$filename);
        }

        return self::url('zummXD2dvAtI.png');
    }

    public static function photoUrl($images)
    {
        foreach (explode(',', (string) $images) as $name) {
            $name = basename(str_replace('\\', '/', urldecode(trim($name))));
            if ($name === '' || strtolower($name) === 'null' || $name === 'zummXD2dvAtI.png') {
                continue;
            }
            foreach (['images/product/'.$name, 'images/product/thumbs/'.$name] as $relative) {
                $path = public_path($relative);
                if (is_file($path) && filesize($path) > 0) {
                    return self::assetFile($relative).'?v='.filemtime($path);
                }
            }
        }

        return null;
    }

    public static function firstName($filename)
    {
        $filename = trim((string) $filename);
        if ($filename === '' || strtolower($filename) === 'null') {
            return 'zummXD2dvAtI.png';
        }
        if (strpos($filename, ',') !== false) {
            $filename = trim(explode(',', $filename)[0]);
        }
        $filename = basename(str_replace('\\', '/', urldecode($filename)));

        return $filename !== '' ? $filename : 'zummXD2dvAtI.png';
    }

    public static function storeUpload($file, $subdir = 'product')
    {
        if (! $file || ! method_exists($file, 'isValid') || ! $file->isValid()) {
            return null;
        }
        $dir = public_path('images/'.$subdir);
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            return null;
        }
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $ext = 'jpg';
        }
        $stamp = date('YmdHis').'_'.substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
        $tmpName = $stamp.'.'.$ext;
        $file->move($dir, $tmpName);
        $tmpPath = $dir.DIRECTORY_SEPARATOR.$tmpName;
        $jpgName = $stamp.'.jpg';
        $jpgPath = $dir.DIRECTORY_SEPARATOR.$jpgName;
        $ok = self::optimizeTo($tmpPath, $jpgPath, self::ORIGINAL_EDGE, self::MAX_BYTES);
        if ($ok && is_file($jpgPath) && filesize($jpgPath) > 0) {
            if ($jpgPath !== $tmpPath) {
                @unlink($tmpPath);
            }
            if ($subdir === 'product') {
                self::writeThumb($jpgPath, $jpgName);
            }
            @chmod($jpgPath, 0644);

            return $jpgName;
        }
        if ($subdir === 'product') {
            self::writeThumb($tmpPath, $tmpName);
        }

        return $tmpName;
    }

    public static function ensureThumb($filename)
    {
        $filename = self::firstName($filename);
        if ($filename === 'zummXD2dvAtI.png') {
            return false;
        }
        $thumb = public_path('images/product/thumbs/'.$filename);
        $thumbInfo = is_file($thumb) ? @getimagesize($thumb) : false;
        $thumbWide = $thumbInfo ? max((int) $thumbInfo[0], (int) $thumbInfo[1]) : 0;
        if (is_file($thumb) && filesize($thumb) > 0 && filesize($thumb) <= self::MAX_BYTES && $thumbWide > 0 && $thumbWide <= self::THUMB_EDGE) {
            return true;
        }
        $orig = public_path('images/product/'.$filename);
        if (! is_file($orig)) {
            return false;
        }

        return self::writeThumb($orig, $filename);
    }

    public static function writeThumb($sourcePath, $filename = null)
    {
        if (! is_file($sourcePath)) {
            return false;
        }
        $filename = $filename ?: basename($sourcePath);
        $destDir = public_path('images/product/thumbs');
        if (! is_dir($destDir) && ! @mkdir($destDir, 0755, true) && ! is_dir($destDir)) {
            return false;
        }

        return self::optimizeTo($sourcePath, $destDir.DIRECTORY_SEPARATOR.$filename, self::THUMB_EDGE, self::MAX_BYTES);
    }

    public static function compressOriginal($sourcePath)
    {
        return self::optimizeFile($sourcePath, self::ORIGINAL_EDGE);
    }

    public static function optimizeFile($sourcePath, $maxEdge = self::ORIGINAL_EDGE)
    {
        if (! is_file($sourcePath)) {
            return false;
        }
        $size = (int) filesize($sourcePath);
        $info = @getimagesize($sourcePath);
        if (! $info) {
            return false;
        }
        $wide = max((int) $info[0], (int) $info[1]);
        if ($size > 0 && $size <= self::MAX_BYTES && $wide <= $maxEdge) {
            return false;
        }

        return self::optimizeTo($sourcePath, $sourcePath, $maxEdge, self::MAX_BYTES);
    }

    public static function resizeTo($sourcePath, $destPath, $maxEdge, $quality)
    {
        return self::optimizeTo($sourcePath, $destPath, $maxEdge, self::MAX_BYTES, $quality);
    }

    public static function laundryIconMap()
    {
        return [
            'LND-TROUSER' => ['file' => 'lnd-trouser.jpg', 'kind' => 'trouser', 'label' => 'Trouser'],
            'LND-SHIRT' => ['file' => 'lnd-shirt.jpg', 'kind' => 'shirt', 'label' => 'Shirt'],
            'LND-SUIT' => ['file' => 'lnd-suit.jpg', 'kind' => 'suit', 'label' => 'Suit'],
        ];
    }

    public static function seedLaundryIcons()
    {
        $dir = public_path('images/product');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        foreach (self::laundryIconMap() as $meta) {
            $path = $dir.DIRECTORY_SEPARATOR.$meta['file'];
            if (! is_file($path) || filesize($path) < 800) {
                self::writeLabeledIcon($path, $meta['kind'], $meta['label']);
            }
            self::writeThumb($path, $meta['file']);
        }

        return self::laundryIconMap();
    }

    public static function optimizeTo($sourcePath, $destPath, $maxEdge, $maxBytes, $startQuality = 82)
    {
        if (! function_exists('imagecreatetruecolor') || ! is_file($sourcePath)) {
            return false;
        }
        $info = @getimagesize($sourcePath);
        if (! $info) {
            return false;
        }
        $src = self::loadImage($sourcePath, $info[2]);
        if (! $src) {
            return false;
        }
        $origW = imagesx($src);
        $origH = imagesy($src);
        $requestedDest = $destPath;
        $destExt = strtolower(pathinfo($destPath, PATHINFO_EXTENSION));
        if (! in_array($destExt, ['jpg', 'jpeg'], true)) {
            $destPath = preg_replace('/\.[^.]+$/', '.jpg', $destPath);
        }
        $edges = [];
        foreach ([(int) $maxEdge, 200, 160, 120] as $edge) {
            if ($edge > 0 && $edge <= (int) $maxEdge && ! in_array($edge, $edges, true)) {
                $edges[] = $edge;
            }
        }
        if (! $edges) {
            $edges = [240];
        }
        $qualities = [(int) $startQuality, 74, 66, 58, 50, 42];
        $ok = false;
        foreach ($edges as $edge) {
            $scale = 1;
            if ($origW > $edge || $origH > $edge) {
                $scale = $edge / max($origW, $origH);
            }
            $nw = max(1, (int) round($origW * $scale));
            $nh = max(1, (int) round($origH * $scale));
            $dst = imagecreatetruecolor($nw, $nh);
            if (! $dst) {
                continue;
            }
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $origW, $origH);
            foreach ($qualities as $quality) {
                if (self::saveJpeg($dst, $destPath, $quality) && filesize($destPath) <= $maxBytes) {
                    $ok = true;
                    imagedestroy($dst);
                    break 2;
                }
                $ok = is_file($destPath);
            }
            imagedestroy($dst);
            if ($ok && is_file($destPath) && filesize($destPath) <= $maxBytes) {
                break;
            }
        }
        imagedestroy($src);
        if ($ok) {
            @chmod($destPath, 0644);
            if ($requestedDest !== $destPath && is_file($requestedDest)) {
                @unlink($requestedDest);
            }
        }

        return $ok;
    }

    /**
     * JPEG bytes saved as .png/.gif/.webp will not paint in Safari. Rename them to .jpg.
     */
    public static function ensureJpegName($path)
    {
        if (! is_file($path)) {
            return null;
        }
        $handle = @fopen($path, 'rb');
        $magic = $handle ? fread($handle, 3) : '';
        if ($handle) {
            fclose($handle);
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $base = basename($path);
        if ($magic !== "\xFF\xD8\xFF" || in_array($ext, ['jpg', 'jpeg'], true)) {
            return $base;
        }
        $dest = preg_replace('/\.[^.]+$/', '.jpg', $path);
        if ($dest === $path) {
            return $base;
        }
        if (is_file($dest)) {
            $dest = preg_replace('/\.jpg$/', '-'.substr(md5($path), 0, 4).'.jpg', $dest);
        }
        if (! @rename($path, $dest)) {
            return $base;
        }
        @chmod($dest, 0644);

        return basename($dest);
    }

    public static function writeLabeledIcon($path, $kind, $label)
    {
        if (! function_exists('imagecreatetruecolor')) {
            return false;
        }
        $size = 400;
        $im = imagecreatetruecolor($size, $size);
        $cream = imagecolorallocate($im, 247, 241, 232);
        $gold = imagecolorallocate($im, 197, 160, 89);
        $dark = imagecolorallocate($im, 10, 10, 10);
        $white = imagecolorallocate($im, 255, 255, 255);
        imagefilledrectangle($im, 0, 0, $size, $size, $cream);
        imagefilledellipse($im, 200, 175, 250, 250, $white);
        imageellipse($im, 200, 175, 250, 250, $gold);
        if ($kind === 'shirt') {
            self::drawShirt($im, $gold, $dark);
        } elseif ($kind === 'suit') {
            self::drawSuit($im, $gold, $dark);
        } else {
            self::drawTrouser($im, $gold, $dark);
        }
        imagefilledrectangle($im, 0, 330, $size, $size, $dark);
        imagestring($im, 5, (int) max(8, 200 - (strlen($label) * 4.5)), 350, strtoupper($label), $gold);
        $ok = self::saveJpeg($im, $path, 80);
        imagedestroy($im);
        if ($ok) {
            self::optimizeFile($path, 400);
        }

        return $ok;
    }

    protected static function drawShirt($im, $gold, $dark)
    {
        $body = [140, 110, 260, 110, 275, 300, 125, 300];
        imagefilledpolygon($im, $body, 4, $gold);
        $left = [140, 115, 70, 145, 90, 195, 145, 160];
        $right = [260, 115, 330, 145, 310, 195, 255, 160];
        imagefilledpolygon($im, $left, 4, $dark);
        imagefilledpolygon($im, $right, 4, $dark);
        $collar = [170, 110, 200, 155, 230, 110];
        imagefilledpolygon($im, $collar, 3, $dark);
    }

    protected static function drawTrouser($im, $gold, $dark)
    {
        imagefilledrectangle($im, 130, 90, 270, 120, $dark);
        $left = [130, 118, 195, 118, 185, 310, 115, 310];
        $right = [205, 118, 270, 118, 285, 310, 215, 310];
        imagefilledpolygon($im, $left, 4, $gold);
        imagefilledpolygon($im, $right, 4, $gold);
        imageline($im, 200, 118, 200, 300, $dark);
    }

    protected static function drawSuit($im, $gold, $dark)
    {
        $jacket = [145, 95, 255, 95, 290, 280, 110, 280];
        imagefilledpolygon($im, $jacket, 4, $dark);
        $lapelL = [200, 115, 145, 95, 155, 250];
        $lapelR = [200, 115, 255, 95, 245, 250];
        imagefilledpolygon($im, $lapelL, 3, $gold);
        imagefilledpolygon($im, $lapelR, 3, $gold);
        $tie = [200, 120, 215, 210, 200, 250, 185, 210];
        imagefilledpolygon($im, $tie, 4, $gold);
        imagefilledrectangle($im, 150, 278, 195, 330, $gold);
        imagefilledrectangle($im, 205, 278, 250, 330, $gold);
    }

    protected static function assetFile($relative)
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        $dir = dirname($relative);
        $name = rawurlencode(basename($relative));
        $path = ($dir === '.' ? $name : $dir.'/'.$name);

        return asset('public/'.$path);
    }

    protected static function loadImage($path, $type)
    {
        if ($type === IMAGETYPE_JPEG) {
            return @imagecreatefromjpeg($path);
        }
        if ($type === IMAGETYPE_PNG) {
            return @imagecreatefrompng($path);
        }
        if ($type === IMAGETYPE_GIF) {
            return @imagecreatefromgif($path);
        }
        if (defined('IMAGETYPE_WEBP') && $type === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) {
            return @imagecreatefromwebp($path);
        }

        return false;
    }

    protected static function saveJpeg($image, $path, $quality)
    {
        $tmp = $path.'.tmp'.mt_rand(1000, 9999);
        $ok = @imagejpeg($image, $tmp, max(30, min(90, (int) $quality)));
        if ($ok && @rename($tmp, $path)) {
            return true;
        }
        @unlink($tmp);

        return false;
    }

    protected static function saveImage($image, $path, $type, $quality)
    {
        return self::saveJpeg($image, $path, $quality);
    }
}
