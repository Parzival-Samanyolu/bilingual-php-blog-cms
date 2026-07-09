<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Articles with per-language translations, category/author joins, search,
 * trending/latest listings and the editorial status workflow.
 *
 * Translation fallback: every listing joins the article_translations row for
 * the requested language and, as a fallback, the row for the article's own base
 * language (articles.lang). COALESCE prefers the requested language. Category
 * names fall back to 'tr'.
 *
 * status: draft | pending | published | rejected
 */
class ArticleModel extends BaseModel
{
    /**
     * Recursive CTE resolving a category id (bound `?`) plus all descendants
     * into `cat_tree`. Prefixed to a following SELECT; the id binds first.
     */
    private const CATEGORY_SUBTREE_CTE = "WITH RECURSIVE cat_tree AS (
                SELECT id FROM `categories` WHERE id = ?
                UNION ALL
                SELECT c.id FROM `categories` c
                INNER JOIN cat_tree ct ON c.parent_id = ct.id
            ) ";

    protected string $table = 'articles';

    /**
     * SELECT + JOIN skeleton shared by listing queries.
     *
     * Consumes exactly TWO bound `?` params, in order:
     *   1. requested language (article translation join)
     *   2. requested language (category translation join)
     *
     * @param bool $withContent whether to include the LONGTEXT content column
     */
    private function listBase(bool $withContent = false): string
    {
        $content = $withContent
            ? 'COALESCE(t.content, tb.content) AS content,'
            : '';

        return "SELECT a.*,
                    COALESCE(t.title, tb.title) AS title,
                    COALESCE(t.excerpt, tb.excerpt) AS excerpt,
                    COALESCE(t.meta_title, tb.meta_title) AS meta_title,
                    COALESCE(t.meta_description, tb.meta_description) AS meta_description,
                    {$content}
                    COALESCE(t.lang, tb.lang) AS translation_lang,
                    u.name AS author_name,
                    u.username AS author_username,
                    u.avatar AS author_avatar,
                    c.slug AS category_slug,
                    COALESCE(ct.name, ctb.name) AS category_name
                FROM `articles` a
                LEFT JOIN `article_translations` t  ON t.article_id = a.id  AND t.lang = ?
                LEFT JOIN `article_translations` tb ON tb.article_id = a.id AND tb.lang = a.lang
                INNER JOIN `users` u ON u.id = a.author_id
                INNER JOIN `categories` c ON c.id = a.category_id
                LEFT JOIN `category_translations` ct  ON ct.category_id = c.id  AND ct.lang = ?
                LEFT JOIN `category_translations` ctb ON ctb.category_id = c.id AND ctb.lang = 'tr'";
    }

    private static function offset(int $page, int $perPage): int
    {
        return max(0, ($page - 1) * $perPage);
    }

    /**
     * Turn a free-text query into a safe BOOLEAN MODE expression: every token
     * becomes a prefix match. Returns '' when nothing usable remains.
     */
    private function booleanQuery(string $query): string
    {
        $cleaned = preg_replace('/[+\-<>()~*"@]+/u', ' ', $query) ?? '';
        $tokens = preg_split('/\s+/u', trim($cleaned), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($tokens === []) {
            return '';
        }

        $tokens = array_map(static fn (string $t): string => $t . '*', $tokens);

        return implode(' ', $tokens);
    }

    /**
     * Find a single published-or-not article by its (globally unique) slug,
     * with content, author and category joined.
     *
     * @return array<string,mixed>|null
     */
    public function findBySlug(string $slug, string $lang): ?array
    {
        $sql = $this->listBase(true) . " WHERE a.slug = ? LIMIT 1";

        return $this->db->fetch($sql, [$lang, $lang, $slug]);
    }

    /**
     * Fetch full details for a single article by id (content included).
     *
     * @return array<string,mixed>|null
     */
    public function getWithDetails(int $id, string $lang): ?array
    {
        $sql = $this->listBase(true) . " WHERE a.id = ? LIMIT 1";

        return $this->db->fetch($sql, [$lang, $lang, $id]);
    }

    /**
     * Articles in a category with a given status, paginated.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getByCategory(
        int $categoryId,
        string $lang,
        string $status = 'published',
        int $page = 1,
        int $perPage = 12,
        bool $includeSubtree = false
    ): array {
        if ($includeSubtree) {
            // The recursive CTE's `?` binds first (textually), then the two lang
            // params inside listBase()'s joins, then the status filter.
            $sql = self::CATEGORY_SUBTREE_CTE . $this->listBase()
                . " WHERE a.category_id IN (SELECT id FROM cat_tree) AND a.status = ?
                 ORDER BY a.created_at DESC
                 LIMIT " . (int) $perPage . " OFFSET " . self::offset($page, $perPage);

            return $this->db->fetchAll($sql, [$categoryId, $lang, $lang, $status]);
        }

        $sql = $this->listBase() . " WHERE a.category_id = ? AND a.status = ?
                 ORDER BY a.created_at DESC
                 LIMIT " . (int) $perPage . " OFFSET " . self::offset($page, $perPage);

        return $this->db->fetchAll($sql, [$lang, $lang, $categoryId, $status]);
    }

    /**
     * Count published-or-status articles in a category (for pagination).
     * With $includeSubtree, counts the category and all its descendants.
     */
    public function countByCategory(
        int $categoryId,
        string $status = 'published',
        bool $includeSubtree = false
    ): int {
        if ($includeSubtree) {
            $row = $this->db->fetch(
                self::CATEGORY_SUBTREE_CTE
                . " SELECT COUNT(*) AS cnt FROM `articles`
                     WHERE `category_id` IN (SELECT id FROM cat_tree) AND `status` = ?",
                [$categoryId, $status]
            );

            return (int) ($row['cnt'] ?? 0);
        }

        $row = $this->db->fetch(
            "SELECT COUNT(*) AS cnt FROM `articles` WHERE `category_id` = ? AND `status` = ?",
            [$categoryId, $status]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Other published articles in the same category, excluding the given one.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getRelated(int $articleId, int $categoryId, string $lang, int $limit = 6): array
    {
        $sql = $this->listBase() . " WHERE a.category_id = ? AND a.id <> ? AND a.status = 'published'
                 ORDER BY a.view_count DESC, a.created_at DESC
                 LIMIT " . (int) $limit;

        return $this->db->fetchAll($sql, [$lang, $lang, $categoryId, $articleId]);
    }

    /**
     * Atomically bump the view counter.
     */
    public function incrementViewCount(int $id): int
    {
        return $this->db->execute(
            "UPDATE `articles` SET `view_count` = `view_count` + 1 WHERE `id` = ?",
            [$id]
        );
    }

    /**
     * Full-text search over published articles in the requested language,
     * optionally filtered by category and/or tag. Ordered by relevance.
     *
     * @return array<int,array<string,mixed>>
     */
    public function search(
        string $query,
        string $lang,
        ?int $categoryId = null,
        ?int $tagId = null,
        int $page = 1,
        int $perPage = 12
    ): array {
        $match = $this->booleanQuery($query);
        if ($match === '') {
            return [];
        }

        // params order tracks the ? placeholders below
        $params = [$lang, $lang, $lang, $match];

        $sql = $this->listBase()
            . " WHERE a.status = 'published'
                AND t.lang = ?
                AND MATCH(t.title, t.content, t.excerpt) AGAINST (? IN BOOLEAN MODE)";

        if ($categoryId !== null) {
            $sql .= " AND a.category_id = ?";
            $params[] = $categoryId;
        }
        if ($tagId !== null) {
            $sql .= " AND EXISTS (SELECT 1 FROM `article_tags` atx
                                  WHERE atx.article_id = a.id AND atx.tag_id = ?)";
            $params[] = $tagId;
        }

        $sql .= " ORDER BY MATCH(t.title, t.content, t.excerpt) AGAINST (? IN BOOLEAN MODE) DESC,
                           a.created_at DESC
                  LIMIT " . (int) $perPage . " OFFSET " . self::offset($page, $perPage);
        $params[] = $match;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Count of full-text matches (for pagination).
     */
    public function countSearch(string $query, string $lang, ?int $categoryId = null, ?int $tagId = null): int
    {
        $match = $this->booleanQuery($query);
        if ($match === '') {
            return 0;
        }

        $params = [$lang, $match];
        $sql = "SELECT COUNT(*) AS cnt
                FROM `articles` a
                INNER JOIN `article_translations` t ON t.article_id = a.id AND t.lang = ?
                WHERE a.status = 'published'
                  AND MATCH(t.title, t.content, t.excerpt) AGAINST (? IN BOOLEAN MODE)";

        if ($categoryId !== null) {
            $sql .= " AND a.category_id = ?";
            $params[] = $categoryId;
        }
        if ($tagId !== null) {
            $sql .= " AND EXISTS (SELECT 1 FROM `article_tags` atx
                                  WHERE atx.article_id = a.id AND atx.tag_id = ?)";
            $params[] = $tagId;
        }

        $row = $this->db->fetch($sql, $params);

        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Latest published articles, paginated.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getLatest(string $lang, int $limit = 12, int $page = 1): array
    {
        $sql = $this->listBase() . " WHERE a.status = 'published'
                 ORDER BY a.created_at DESC
                 LIMIT " . (int) $limit . " OFFSET " . self::offset($page, $limit);

        return $this->db->fetchAll($sql, [$lang, $lang]);
    }

    /**
     * Count of all published articles (for pagination of getLatest).
     */
    public function countPublished(): int
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS cnt FROM `articles` WHERE `status` = 'published'"
        );

        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Trending published articles: most-viewed among those created recently.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getTrending(string $lang, int $limit = 6, int $days = 7): array
    {
        $sql = $this->listBase() . " WHERE a.status = 'published'
                 AND a.created_at >= (NOW() - INTERVAL " . (int) $days . " DAY)
                 ORDER BY a.view_count DESC, a.created_at DESC
                 LIMIT " . (int) $limit;

        $rows = $this->db->fetchAll($sql, [$lang, $lang]);

        // Fall back to all-time most-viewed when nothing is recent enough, so
        // the trending strip is never empty on an archive of older content.
        if ($rows === []) {
            $sql = $this->listBase() . " WHERE a.status = 'published'
                     ORDER BY a.view_count DESC, a.created_at DESC
                     LIMIT " . (int) $limit;
            $rows = $this->db->fetchAll($sql, [$lang, $lang]);
        }

        return $rows;
    }

    /**
     * Articles awaiting review (status = pending), oldest first.
     * Uses the article base language for display.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getPending(): array
    {
        $sql = $this->listBaseNoLang() . " WHERE a.status = 'pending'
                 ORDER BY a.created_at ASC";

        return $this->db->fetchAll($sql);
    }

    /**
     * Articles by a given author, optionally filtered by status, paginated.
     * Uses the article base language for display.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getByAuthor(int $authorId, ?string $status = null, int $page = 1, int $perPage = 12): array
    {
        $sql = $this->listBaseNoLang() . " WHERE a.author_id = ?";
        $params = [$authorId];

        if ($status !== null) {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY a.created_at DESC
                  LIMIT " . (int) $perPage . " OFFSET " . self::offset($page, $perPage);

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Count of an author's articles (optionally filtered by status).
     */
    public function countByAuthor(int $authorId, ?string $status = null): int
    {
        $sql = "SELECT COUNT(*) AS cnt FROM `articles` WHERE `author_id` = ?";
        $params = [$authorId];
        if ($status !== null) {
            $sql .= " AND `status` = ?";
            $params[] = $status;
        }

        $row = $this->db->fetch($sql, $params);

        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Listing skeleton that displays each article in its OWN base language
     * (no requested-language param). Used by admin/author panels.
     */
    private function listBaseNoLang(): string
    {
        return "SELECT a.*,
                    tb.title AS title,
                    tb.excerpt AS excerpt,
                    tb.meta_title AS meta_title,
                    tb.meta_description AS meta_description,
                    tb.lang AS translation_lang,
                    u.name AS author_name,
                    u.username AS author_username,
                    u.avatar AS author_avatar,
                    c.slug AS category_slug,
                    ctb.name AS category_name
                FROM `articles` a
                LEFT JOIN `article_translations` tb ON tb.article_id = a.id AND tb.lang = a.lang
                INNER JOIN `users` u ON u.id = a.author_id
                INNER JOIN `categories` c ON c.id = a.category_id
                LEFT JOIN `category_translations` ctb ON ctb.category_id = c.id AND ctb.lang = 'tr'";
    }

    /**
     * Transition an article to published.
     */
    public function publish(int $id): int
    {
        return $this->db->execute(
            "UPDATE `articles` SET `status` = 'published' WHERE `id` = ?",
            [$id]
        );
    }

    /**
     * Transition an article to rejected. The schema has no rejection-reason
     * column; $reason is accepted for API compatibility but not persisted here.
     */
    public function reject(int $id, ?string $reason = null): int
    {
        return $this->db->execute(
            "UPDATE `articles` SET `status` = 'rejected' WHERE `id` = ?",
            [$id]
        );
    }

    /**
     * Transition a draft to pending review.
     */
    public function submitForReview(int $id): int
    {
        return $this->db->execute(
            "UPDATE `articles` SET `status` = 'pending' WHERE `id` = ?",
            [$id]
        );
    }
}
