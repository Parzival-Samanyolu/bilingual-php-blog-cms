<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\SettingModel;

/**
 * Very small file-based full-page (HTML fragment) cache.
 *
 * Each entry is stored as cache/{md5(key)}.html under the project base
 * directory. An entry is considered fresh while its mtime is newer than
 * (now - TTL), where TTL comes from the `cache_ttl` setting (seconds).
 *
 * All methods are static and null-/error-safe: caching never throws, it simply
 * degrades to a miss so a failed cache can never take the site down.
 */
final class Cache
{
    /** Fallback TTL (seconds) when the cache_ttl setting is missing/invalid. */
    private const DEFAULT_TTL = 3600;

    private static ?int $ttl = null;

    /**
     * Return cached HTML for $key when a fresh entry exists, otherwise null.
     */
    public static function get(string $key): ?string
    {
        $file = self::pathFor($key);
        if (!is_file($file)) {
            return null;
        }

        $mtime = @filemtime($file);
        if ($mtime === false || $mtime < (time() - self::ttl())) {
            return null;
        }

        $contents = @file_get_contents($file);

        return $contents === false ? null : $contents;
    }

    /**
     * Write $content to the cache under $key. Returns true on success.
     */
    public static function set(string $key, string $content): bool
    {
        $dir = self::dir();
        if (!self::ensureDir($dir)) {
            return false;
        }

        $file = self::pathFor($key);
        $tmp  = $file . '.' . getmypid() . '.tmp';

        if (@file_put_contents($tmp, $content, LOCK_EX) === false) {
            return false;
        }

        // Atomic replace so concurrent readers never see a partial file.
        if (!@rename($tmp, $file)) {
            @unlink($tmp);

            return false;
        }

        return true;
    }

    /**
     * Delete the cache entry for $key. Returns true if nothing remains cached.
     */
    public static function bust(string $key): bool
    {
        $file = self::pathFor($key);
        if (!is_file($file)) {
            return true;
        }

        return @unlink($file);
    }

    /**
     * Delete every cache file. $pattern is matched against the ORIGINAL cache
     * keys is impossible once hashed, so this globs the raw *.html files; pass
     * '*' (default) to clear everything. A non-default pattern is treated as a
     * glob against the stored filenames (md5 hashes + .html).
     *
     * @return int number of files removed
     */
    public static function bustPattern(string $pattern = '*'): int
    {
        $dir = self::dir();
        if (!is_dir($dir)) {
            return 0;
        }

        $glob = ($pattern === '' || $pattern === '*')
            ? $dir . '/*.html'
            : $dir . '/' . $pattern;

        $files = glob($glob);
        if ($files === false) {
            return 0;
        }

        $removed = 0;
        foreach ($files as $file) {
            if (is_file($file) && @unlink($file)) {
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Remove all expired cache files (housekeeping helper).
     *
     * @return int number of stale files removed
     */
    public static function prune(): int
    {
        $dir = self::dir();
        if (!is_dir($dir)) {
            return 0;
        }

        $files = glob($dir . '/*.html');
        if ($files === false) {
            return 0;
        }

        $threshold = time() - self::ttl();
        $removed   = 0;
        foreach ($files as $file) {
            $mtime = @filemtime($file);
            if ($mtime !== false && $mtime < $threshold && @unlink($file)) {
                $removed++;
            }
        }

        return $removed;
    }

    // ---------------------------------------------------------------------
    // Internals
    // ---------------------------------------------------------------------

    /**
     * Absolute filesystem path for a given cache key.
     */
    private static function pathFor(string $key): string
    {
        return self::dir() . '/' . md5($key) . '.html';
    }

    /**
     * The cache directory: BASE/cache.
     */
    private static function dir(): string
    {
        if (defined('BASE_PATH')) {
            return rtrim((string) constant('BASE_PATH'), '/') . '/cache';
        }

        return dirname(__DIR__, 2) . '/cache';
    }

    private static function ensureDir(string $dir): bool
    {
        if (is_dir($dir)) {
            return true;
        }

        return @mkdir($dir, 0775, true) || is_dir($dir);
    }

    /**
     * Resolve the TTL (seconds) from settings, cached per request.
     */
    private static function ttl(): int
    {
        if (self::$ttl !== null) {
            return self::$ttl;
        }

        $ttl = self::DEFAULT_TTL;
        try {
            $raw = (new SettingModel())->get('cache_ttl', (string) self::DEFAULT_TTL);
            if ($raw !== null && ctype_digit(trim($raw)) && (int) $raw > 0) {
                $ttl = (int) $raw;
            }
        } catch (\Throwable) {
            $ttl = self::DEFAULT_TTL;
        }

        self::$ttl = $ttl;

        return $ttl;
    }
}
