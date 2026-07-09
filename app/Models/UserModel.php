<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Users: accounts, roles, approval workflow and author statistics.
 *
 * is_approved semantics:
 *   0  = pending / not yet approved
 *   1  = approved / active
 *  -1  = banned
 */
class UserModel extends BaseModel
{
    protected string $table = 'users';

    /**
     * @return array<string,mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `users` WHERE `email` = ? LIMIT 1",
            [$email]
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findByUsername(string $username): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `users` WHERE `username` = ? LIMIT 1",
            [$username]
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findByGoogleId(string $googleId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `users` WHERE `google_id` = ? LIMIT 1",
            [$googleId]
        );
    }

    /**
     * Approve an account (is_approved = 1). Returns affected rows.
     */
    public function approveUser(int $id): int
    {
        return $this->db->execute(
            "UPDATE `users` SET `is_approved` = 1 WHERE `id` = ?",
            [$id]
        );
    }

    /**
     * Set the account role. Invalid roles are ignored.
     */
    public function setRole(int $id, string $role): int
    {
        if (!in_array($role, ['admin', 'author', 'reader'], true)) {
            return 0;
        }

        return $this->db->execute(
            "UPDATE `users` SET `role` = ? WHERE `id` = ?",
            [$role, $id]
        );
    }

    /**
     * Ban an account (is_approved = -1).
     */
    public function banUser(int $id): int
    {
        return $this->db->execute(
            "UPDATE `users` SET `is_approved` = -1 WHERE `id` = ?",
            [$id]
        );
    }

    /**
     * Reinstate a banned/pending account (is_approved = 1).
     */
    public function unbanUser(int $id): int
    {
        return $this->db->execute(
            "UPDATE `users` SET `is_approved` = 1 WHERE `id` = ?",
            [$id]
        );
    }

    /**
     * Authors awaiting approval.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getPendingAuthors(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `users`
             WHERE `role` = 'author' AND `is_approved` = 0
             ORDER BY `created_at` ASC"
        );
    }

    /**
     * Aggregate stats for an author across all of their articles.
     *
     * @return array{article_count:int,total_views:int}
     */
    public function getAuthorStats(int $id): array
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS article_count, COALESCE(SUM(`view_count`), 0) AS total_views
             FROM `articles` WHERE `author_id` = ?",
            [$id]
        );

        return [
            'article_count' => (int) ($row['article_count'] ?? 0),
            'total_views'   => (int) ($row['total_views'] ?? 0),
        ];
    }
}
