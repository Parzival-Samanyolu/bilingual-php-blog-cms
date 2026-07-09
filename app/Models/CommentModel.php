<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Article comments with a single level of threading (parent -> children) and a
 * moderation workflow.
 *
 * is_approved: 0 = pending, 1 = approved.
 */
class CommentModel extends BaseModel
{
    protected string $table = 'comments';

    /**
     * Comments for an article as a threaded tree. Each returned comment carries
     * a `children` array. When $approved is true only approved comments are
     * returned; otherwise all comments are returned (moderation view).
     *
     * @return array<int,array<string,mixed>>
     */
    public function getForArticle(int $articleId, bool $approved = true): array
    {
        $sql = "SELECT cm.*, u.name AS user_name, u.username AS user_username, u.avatar AS user_avatar
                FROM `comments` cm
                INNER JOIN `users` u ON u.id = cm.user_id
                WHERE cm.article_id = ?";
        $params = [$articleId];

        if ($approved) {
            $sql .= " AND cm.is_approved = 1";
        }

        $sql .= " ORDER BY cm.created_at ASC";

        $rows = $this->db->fetchAll($sql, $params);

        $nodes = [];
        foreach ($rows as $row) {
            $row['children'] = [];
            $nodes[(int) $row['id']] = $row;
        }

        $tree = [];
        foreach ($nodes as $id => &$node) {
            $parentId = $node['parent_id'] !== null ? (int) $node['parent_id'] : null;
            if ($parentId !== null && isset($nodes[$parentId])) {
                $nodes[$parentId]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }
        unset($node);

        return $tree;
    }

    /**
     * Pending comments across all articles, with article + author context.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getPending(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT cm.*,
                    u.name AS user_name, u.username AS user_username,
                    a.slug AS article_slug,
                    COALESCE(t.title, tb.title) AS article_title
                FROM `comments` cm
                INNER JOIN `users` u ON u.id = cm.user_id
                INNER JOIN `articles` a ON a.id = cm.article_id
                LEFT JOIN `article_translations` t  ON t.article_id = a.id  AND t.lang = a.lang
                LEFT JOIN `article_translations` tb ON tb.article_id = a.id AND tb.lang = 'tr'
                WHERE cm.is_approved = 0
                ORDER BY cm.created_at ASC
                LIMIT " . (int) $limit . " OFFSET " . max(0, (int) $offset);

        return $this->db->fetchAll($sql);
    }

    /**
     * Approve a comment (is_approved = 1).
     */
    public function approve(int $id): int
    {
        return $this->db->execute(
            "UPDATE `comments` SET `is_approved` = 1 WHERE `id` = ?",
            [$id]
        );
    }

    /**
     * Reject a comment. There is no rejected state in the schema, so the
     * comment (and, via cascade, its replies) is deleted.
     */
    public function reject(int $id): int
    {
        return $this->db->execute(
            "DELETE FROM `comments` WHERE `id` = ?",
            [$id]
        );
    }

    /**
     * Number of comments awaiting moderation.
     */
    public function countPending(): int
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS cnt FROM `comments` WHERE `is_approved` = 0"
        );

        return (int) ($row['cnt'] ?? 0);
    }
}
