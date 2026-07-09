<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Base class for all models.
 *
 * Provides generic CRUD built exclusively from bound parameters. Column names
 * used to build WHERE/SET clauses come from array keys supplied by the calling
 * (developer) code, never from raw user input, and are always backtick-quoted.
 */
abstract class BaseModel
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Fetch a single row by primary key.
     *
     * @return array<string,mixed>|null
     */
    public function find(int|string $id): ?array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1";

        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Fetch many rows filtered by equality conditions.
     *
     * @param array<string,mixed> $conditions column => value (ANDed together)
     * @param string|null         $orderBy    raw ORDER BY expression (developer supplied, never user input)
     * @return array<int,array<string,mixed>>
     */
    public function findAll(
        array $conditions = [],
        ?int $limit = null,
        ?int $offset = null,
        ?string $orderBy = null
    ): array {
        $sql = "SELECT * FROM `{$this->table}`";
        $params = [];

        if ($conditions !== []) {
            $clauses = [];
            foreach ($conditions as $column => $value) {
                $clauses[] = "`{$column}` = ?";
                $params[] = $value;
            }
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        if ($orderBy !== null && $orderBy !== '') {
            $sql .= ' ORDER BY ' . $orderBy;
        }

        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit;
            if ($offset !== null) {
                $sql .= ' OFFSET ' . (int) $offset;
            }
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Insert a row and return its new primary key.
     *
     * @param array<string,mixed> $data column => value
     */
    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $columnList = implode(', ', array_map(static fn (string $c): string => "`{$c}`", $columns));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $sql = "INSERT INTO `{$this->table}` ({$columnList}) VALUES ({$placeholders})";
        $this->db->execute($sql, array_values($data));

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a row by primary key. Returns affected row count.
     *
     * @param array<string,mixed> $data column => value
     */
    public function update(int|string $id, array $data): int
    {
        if ($data === []) {
            return 0;
        }

        $sets = [];
        $params = [];
        foreach ($data as $column => $value) {
            $sets[] = "`{$column}` = ?";
            $params[] = $value;
        }
        $params[] = $id;

        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $sets)
            . " WHERE `{$this->primaryKey}` = ?";

        return $this->db->execute($sql, $params);
    }

    /**
     * Delete a row by primary key. Returns affected row count.
     */
    public function delete(int|string $id): int
    {
        $sql = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?";

        return $this->db->execute($sql, [$id]);
    }
}
