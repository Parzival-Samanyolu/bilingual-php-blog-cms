<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Key/value site settings. Values are stored (and returned) as strings.
 *
 * The `settings` table uses reserved words `key` and `value` for its columns,
 * so both are always backtick-quoted. A process-wide static cache is loaded
 * lazily on first access and kept in sync by set()/setMultiple().
 */
class SettingModel extends BaseModel
{
    protected string $table = 'settings';
    protected string $primaryKey = 'key';

    /** @var array<string,string>|null */
    private static ?array $cache = null;

    /**
     * Load every setting into the static cache (once).
     */
    private static function loadCache(): void
    {
        if (self::$cache !== null) {
            return;
        }

        $rows = Database::getInstance()->fetchAll("SELECT `key`, `value` FROM `settings`");
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['key']] = $row['value'] === null ? '' : (string) $row['value'];
        }
        self::$cache = $map;
    }

    /**
     * Get a setting value (string) or the default when absent.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        self::loadCache();

        return self::$cache[$key] ?? $default;
    }

    /**
     * Upsert a single setting and update the cache.
     */
    public static function set(string $key, string $value): int
    {
        $affected = Database::getInstance()->execute(
            "INSERT INTO `settings` (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
            [$key, $value]
        );

        self::loadCache();
        self::$cache[$key] = $value;

        return $affected;
    }

    /**
     * All settings as an associative array key => value.
     *
     * @return array<string,string>
     */
    public static function getAll(): array
    {
        self::loadCache();

        return self::$cache;
    }

    /**
     * Upsert several settings at once (transactional).
     *
     * @param array<string,string> $assoc key => value
     */
    public static function setMultiple(array $assoc): void
    {
        if ($assoc === []) {
            return;
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            foreach ($assoc as $key => $value) {
                $db->execute(
                    "INSERT INTO `settings` (`key`, `value`) VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
                    [(string) $key, (string) $value]
                );
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }

        self::loadCache();
        foreach ($assoc as $key => $value) {
            self::$cache[(string) $key] = (string) $value;
        }
    }

    /**
     * Clear the static cache (useful in tests / long-running processes).
     */
    public static function flushCache(): void
    {
        self::$cache = null;
    }
}
