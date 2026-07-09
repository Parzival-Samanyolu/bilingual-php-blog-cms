<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Tags with per-language translations and article associations.
 * Translation fallback language is 'tr'.
 */
class TagModel extends BaseModel
{
    protected string $table = 'tags';

    /**
     * @return array<string,mixed>|null
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `tags` WHERE `slug` = ? LIMIT 1",
            [$slug]
        );
    }

    /**
     * Tags attached to an article, translated.
     *
     * @return array<int,array{id:int,slug:string,name:string}>
     */
    public function getForArticle(int $articleId, string $lang): array
    {
        return $this->db->fetchAll(
            "SELECT tg.id, tg.slug, COALESCE(tt.name, ttb.name) AS name
             FROM `article_tags` at
             INNER JOIN `tags` tg ON tg.id = at.tag_id
             LEFT JOIN `tag_translations` tt  ON tt.tag_id = tg.id  AND tt.lang = ?
             LEFT JOIN `tag_translations` ttb ON ttb.tag_id = tg.id AND ttb.lang = 'tr'
             WHERE at.article_id = ?
             ORDER BY name ASC",
            [$lang, $articleId]
        );
    }

    /**
     * Published articles carrying a given tag, paginated.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getArticlesByTag(int $tagId, string $lang, int $page = 1, int $perPage = 12): array
    {
        $offset = max(0, ($page - 1) * $perPage);

        $sql = "SELECT a.*,
                    COALESCE(t.title, tb.title) AS title,
                    COALESCE(t.excerpt, tb.excerpt) AS excerpt,
                    COALESCE(t.lang, tb.lang) AS translation_lang,
                    u.name AS author_name,
                    u.username AS author_username,
                    u.avatar AS author_avatar,
                    c.slug AS category_slug,
                    COALESCE(ct.name, ctb.name) AS category_name
                FROM `article_tags` at
                INNER JOIN `articles` a ON a.id = at.article_id AND a.status = 'published'
                LEFT JOIN `article_translations` t  ON t.article_id = a.id  AND t.lang = ?
                LEFT JOIN `article_translations` tb ON tb.article_id = a.id AND tb.lang = a.lang
                INNER JOIN `users` u ON u.id = a.author_id
                INNER JOIN `categories` c ON c.id = a.category_id
                LEFT JOIN `category_translations` ct  ON ct.category_id = c.id  AND ct.lang = ?
                LEFT JOIN `category_translations` ctb ON ctb.category_id = c.id AND ctb.lang = 'tr'
                WHERE at.tag_id = ?
                ORDER BY a.created_at DESC
                LIMIT " . (int) $perPage . " OFFSET " . $offset;

        return $this->db->fetchAll($sql, [$lang, $lang, $tagId]);
    }

    /**
     * Count of published articles carrying a tag (for pagination).
     */
    public function countArticlesByTag(int $tagId): int
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS cnt
             FROM `article_tags` at
             INNER JOIN `articles` a ON a.id = at.article_id AND a.status = 'published'
             WHERE at.tag_id = ?",
            [$tagId]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Replace an article's tag set with the given tag ids (transactional).
     *
     * @param array<int,int|string> $tagIds
     */
    public function syncArticleTags(int $articleId, array $tagIds): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "DELETE FROM `article_tags` WHERE `article_id` = ?",
                [$articleId]
            );

            $seen = [];
            foreach ($tagIds as $tagId) {
                $tid = (int) $tagId;
                if ($tid <= 0 || isset($seen[$tid])) {
                    continue;
                }
                $seen[$tid] = true;
                $this->db->execute(
                    "INSERT INTO `article_tags` (`article_id`, `tag_id`) VALUES (?, ?)",
                    [$articleId, $tid]
                );
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Most-used tags, translated, ordered by article count.
     *
     * @return array<int,array{id:int,slug:string,name:string,article_count:int}>
     */
    public function getPopular(string $lang, int $limit = 20): array
    {
        return $this->db->fetchAll(
            "SELECT tg.id, tg.slug,
                    COALESCE(tt.name, ttb.name) AS name,
                    COUNT(at.article_id) AS article_count
             FROM `tags` tg
             LEFT JOIN `tag_translations` tt  ON tt.tag_id = tg.id  AND tt.lang = ?
             LEFT JOIN `tag_translations` ttb ON ttb.tag_id = tg.id AND ttb.lang = 'tr'
             LEFT JOIN `article_tags` at ON at.tag_id = tg.id
             GROUP BY tg.id, tg.slug, name
             ORDER BY article_count DESC, name ASC
             LIMIT " . (int) $limit,
            [$lang]
        );
    }
}
