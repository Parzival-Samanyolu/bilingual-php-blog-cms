<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Article likes. Composite primary key (user_id, article_id); the generic
 * BaseModel single-key CRUD is not used here.
 */
class LikeModel extends BaseModel
{
    protected string $table = 'likes';

    /**
     * Toggle a like. Returns true if the article is liked AFTER the call
     * (i.e. a like was just created), false if the like was removed.
     */
    public function toggle(int $userId, int $articleId): bool
    {
        if ($this->userLiked($userId, $articleId)) {
            $this->db->execute(
                "DELETE FROM `likes` WHERE `user_id` = ? AND `article_id` = ?",
                [$userId, $articleId]
            );

            return false;
        }

        $this->db->execute(
            "INSERT INTO `likes` (`user_id`, `article_id`) VALUES (?, ?)",
            [$userId, $articleId]
        );

        return true;
    }

    /**
     * Total likes for an article.
     */
    public function count(int $articleId): int
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS cnt FROM `likes` WHERE `article_id` = ?",
            [$articleId]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Whether a user has liked an article.
     */
    public function userLiked(int $userId, int $articleId): bool
    {
        $row = $this->db->fetch(
            "SELECT 1 AS x FROM `likes` WHERE `user_id` = ? AND `article_id` = ? LIMIT 1",
            [$userId, $articleId]
        );

        return $row !== null;
    }
}
