<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * PDO singleton wrapper.
 *
 * Reads DB_HOST, DB_NAME, DB_USER, DB_PASS from $_ENV.
 * All queries MUST use bound parameters — values are never interpolated.
 */
final class Database
{
    private static ?Database $instance = null;

    private PDO $pdo;

    private function __construct()
    {
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $name = $_ENV['DB_NAME'] ?? '';
        $user = $_ENV['DB_USER'] ?? '';
        $pass = $_ENV['DB_PASS'] ?? '';
        $port = $_ENV['DB_PORT'] ?? '3306';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $name
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), (int) $e->getCode());
        }
    }

    private function __clone(): void
    {
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Expose the raw PDO handle when a caller needs it directly.
     */
    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * @param array<int|string, mixed> $params
     */
    private function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    /**
     * Fetch a single associative row (or null when there is no match).
     *
     * @param array<int|string, mixed> $params
     * @return array<string, mixed>|null
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Fetch all matching rows as an array of associative rows.
     *
     * @param array<int|string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    /**
     * Execute a write (INSERT/UPDATE/DELETE) and return affected row count.
     *
     * @param array<int|string, mixed> $params
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    /**
     * Fetch a single scalar value from the first column of the first row.
     *
     * @param array<int|string, mixed> $params
     */
    public function fetchColumn(string $sql, array $params = []): mixed
    {
        $value = $this->run($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        if ($this->pdo->inTransaction()) {
            return false;
        }

        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        if (!$this->pdo->inTransaction()) {
            return false;
        }

        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        if (!$this->pdo->inTransaction()) {
            return false;
        }

        return $this->pdo->rollBack();
    }
}
