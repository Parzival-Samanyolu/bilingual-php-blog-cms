<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Article bookmarks. Composite primary key (user_id, article_id); the generic
 * BaseModel single-key CRUD is not used here.
 */
class BookmarkModel extends BaseModel
{
    protected string $table = 'bookmarks';

    /**
     * Toggle a bookmark. Returns true if the article is bookmarked AFTER the
     * call (i.e. a bookmark was just created), false if it was removed.
     */
    public function toggle(int $userId, int $articleId): bool
    {
        if ($this->userBookmarked($userId, $articleId)) {
            $this->db->execute(
                "DELETE FROM `bookmarks` WHERE `user_id` = ? AND `article_id` = ?",
                [$userId, $articleId]
            );

            return false;
        }

        $this->db->execute(
            "INSERT INTO `bookmarks` (`user_id`, `article_id`) VALUES (?, ?)",
            [$userId, $articleId]
        );

        return true;
    }

    /**
     * A user's bookmarked articles, newest bookmark first, paginated.
     * Each article is shown in its own base language.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getUserBookmarks(int $userId, int $page = 1, int $perPage = 12): array
    {
        $offset = max(0, ($page - 1) * $perPage);

        $sql = "SELECT a.*,
                    tb.title AS title,
                    tb.excerpt AS excerpt,
                    tb.lang AS translation_lang,
                    u.name AS author_name,
                    u.username AS author_username,
                    c.slug AS category_slug,
                    ctb.name AS category_name,
                    b.created_at AS bookmarked_at
                FROM `bookmarks` b
                INNER JOIN `articles` a ON a.id = b.article_id
                LEFT JOIN `article_translations` tb ON tb.article_id = a.id AND tb.lang = a.lang
                INNER JOIN `users` u ON u.id = a.author_id
                INNER JOIN `categories` c ON c.id = a.category_id
                LEFT JOIN `category_translations` ctb ON ctb.category_id = c.id AND ctb.lang = 'tr'
                WHERE b.user_id = ?
                ORDER BY b.created_at DESC
                LIMIT " . (int) $perPage . " OFFSET " . $offset;

        return $this->db->fetchAll($sql, [$userId]);
    }

    /**
     * Count of a user's bookmarks (for pagination).
     */
    public function countUserBookmarks(int $userId): int
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS cnt FROM `bookmarks` WHERE `user_id` = ?",
            [$userId]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Whether a user has bookmarked an article.
     */
    public function userBookmarked(int $userId, int $articleId): bool
    {
        $row = $this->db->fetch(
            "SELECT 1 AS x FROM `bookmarks` WHERE `user_id` = ? AND `article_id` = ? LIMIT 1",
            [$userId, $articleId]
        );

        return $row !== null;
    }
}
