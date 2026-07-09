<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Handles image uploads: validates the real MIME type, converts to WebP and
 * stores the file under public/uploads/{subdir}/{Y}/{m}/. Uses GD, falling
 * back to Imagick when GD is unavailable.
 */
final class Image
{
    private const WEBP_QUALITY = 82;

    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpeg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    /**
     * Process a single $_FILES entry and return the public-relative path
     * (e.g. /uploads/articles/2026/07/abc123.webp).
     *
     * @param array<string, mixed> $file A $_FILES[...] entry.
     */
    public static function upload(array $file, string $subdir = 'articles'): string
    {
        if (!isset($file['tmp_name'], $file['error'])) {
            throw new RuntimeException('Invalid upload payload.');
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('File upload error code: ' . (int) $file['error']);
        }

        $tmp = (string) $file['tmp_name'];
        if (!is_uploaded_file($tmp) && !is_file($tmp)) {
            throw new RuntimeException('Uploaded file is missing.');
        }

        $mime = self::detectMime($tmp);
        if (!isset(self::ALLOWED_MIME[$mime])) {
            throw new RuntimeException('Unsupported image type: ' . $mime);
        }

        [$relPath, $absPath] = self::buildTargetPath($subdir);

        if (self::gdAvailable()) {
            self::convertWithGd($tmp, self::ALLOWED_MIME[$mime], $absPath);
        } elseif (self::imagickAvailable()) {
            self::convertWithImagick($tmp, $absPath);
        } else {
            throw new RuntimeException('No image processing extension (GD or Imagick) is available.');
        }

        return $relPath;
    }

    /**
     * Delete a previously stored image by its public-relative path.
     */
    public static function delete(string $relPath): bool
    {
        $relPath = '/' . ltrim($relPath, '/');
        // Only allow deletions inside the uploads directory.
        if (!str_starts_with($relPath, '/uploads/')) {
            return false;
        }

        $abs = self::publicRoot() . $relPath;
        $real = realpath($abs);
        $uploadsReal = realpath(self::publicRoot() . '/uploads');

        if ($real === false || $uploadsReal === false || !str_starts_with($real, $uploadsReal)) {
            return false;
        }

        if (is_file($real)) {
            return unlink($real);
        }

        return false;
    }

    // ---------------------------------------------------------------------
    // Internals
    // ---------------------------------------------------------------------

    private static function detectMime(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new RuntimeException('Unable to inspect file MIME type.');
        }
        $mime = (string) finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mime;
    }

    /**
     * @return array{0:string, 1:string} [relativePath, absolutePath]
     */
    private static function buildTargetPath(string $subdir): array
    {
        $subdir = preg_replace('/[^a-z0-9_\-]/', '', strtolower($subdir)) ?: 'misc';
        $year   = date('Y');
        $month  = date('m');
        $dir    = self::publicRoot() . "/uploads/{$subdir}/{$year}/{$month}";

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Could not create upload directory.');
        }

        $filename = str_replace('.', '', uniqid('', true)) . '.webp';
        $rel      = "/uploads/{$subdir}/{$year}/{$month}/{$filename}";
        $abs      = self::publicRoot() . $rel;

        return [$rel, $abs];
    }

    private static function convertWithGd(string $source, string $type, string $dest): void
    {
        $image = match ($type) {
            'jpeg'  => imagecreatefromjpeg($source),
            'png'   => imagecreatefrompng($source),
            'gif'   => imagecreatefromgif($source),
            'webp'  => imagecreatefromwebp($source),
            default => false,
        };

        if ($image === false) {
            throw new RuntimeException('Failed to decode source image.');
        }

        // Preserve transparency for formats that support it.
        if ($type === 'png' || $type === 'gif' || $type === 'webp') {
            imagepalettetotruecolor($image);
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        if (!imagewebp($image, $dest, self::WEBP_QUALITY)) {
            imagedestroy($image);
            throw new RuntimeException('Failed to write WebP image.');
        }

        imagedestroy($image);
    }

    private static function convertWithImagick(string $source, string $dest): void
    {
        /** @var \Imagick $img */
        $img = new \Imagick($source);
        $img->setImageFormat('webp');
        $img->setImageCompressionQuality(self::WEBP_QUALITY);
        if (!$img->writeImage($dest)) {
            $img->clear();
            throw new RuntimeException('Failed to write WebP image (Imagick).');
        }
        $img->clear();
        $img->destroy();
    }

    private static function gdAvailable(): bool
    {
        return extension_loaded('gd') && function_exists('imagewebp');
    }

    private static function imagickAvailable(): bool
    {
        return extension_loaded('imagick') && class_exists('\Imagick');
    }

    private static function publicRoot(): string
    {
        return dirname(__DIR__, 2) . '/public';
    }
}
