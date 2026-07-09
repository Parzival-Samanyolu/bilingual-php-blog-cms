<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Categories with per-language translations, hierarchy helpers and article counts.
 *
 * The default/base translation language is 'tr'; when a translation is missing
 * for the requested language the Turkish translation is used as a fallback.
 */
class CategoryModel extends BaseModel
{
    protected string $table = 'categories';

    /**
     * @return array<string,mixed>|null
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `categories` WHERE `slug` = ? LIMIT 1",
            [$slug]
        );
    }

    /**
     * Fetch a category with its translated name/description.
     *
     * @return array<string,mixed>|null
     */
    public function getWithTranslation(int $id, string $lang): ?array
    {
        return $this->db->fetch(
            "SELECT c.*,
                    COALESCE(t.name, tb.name) AS name,
                    COALESCE(t.description, tb.description) AS description
             FROM `categories` c
             LEFT JOIN `category_translations` t  ON t.category_id = c.id  AND t.lang = ?
             LEFT JOIN `category_translations` tb ON tb.category_id = c.id AND tb.lang = 'tr'
             WHERE c.id = ?
             LIMIT 1",
            [$lang, $id]
        );
    }

    /**
     * Direct children of a category (translated name, defaults to 'tr').
     *
     * @return array<int,array<string,mixed>>
     */
    public function getChildren(int $parentId): array
    {
        return $this->db->fetchAll(
            "SELECT c.*, t.name
             FROM `categories` c
             LEFT JOIN `category_translations` t ON t.category_id = c.id AND t.lang = 'tr'
             WHERE c.parent_id = ?
             ORDER BY c.sort_order ASC, t.name ASC",
            [$parentId]
        );
    }

    /**
     * Full category tree as a nested array. Each node carries a `children` key.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getTree(string $lang): array
    {
        $rows = $this->db->fetchAll(
            "SELECT c.id, c.parent_id, c.slug, c.sort_order,
                    COALESCE(t.name, tb.name) AS name
             FROM `categories` c
             LEFT JOIN `category_translations` t  ON t.category_id = c.id  AND t.lang = ?
             LEFT JOIN `category_translations` tb ON tb.category_id = c.id AND tb.lang = 'tr'
             ORDER BY c.sort_order ASC, name ASC",
            [$lang]
        );

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
     * Breadcrumb chain from the root down to the given category (inclusive).
     *
     * @return array<int,array{name:string,url:string,slug:string}>
     */
    public function getBreadcrumb(int $categoryId, string $lang): array
    {
        $chain = [];
        $currentId = $categoryId;
        $guard = 0;

        while ($currentId !== null && $guard < 50) {
            $row = $this->db->fetch(
                "SELECT c.id, c.parent_id, c.slug,
                        COALESCE(t.name, tb.name) AS name
                 FROM `categories` c
                 LEFT JOIN `category_translations` t  ON t.category_id = c.id  AND t.lang = ?
                 LEFT JOIN `category_translations` tb ON tb.category_id = c.id AND tb.lang = 'tr'
                 WHERE c.id = ?
                 LIMIT 1",
                [$lang, $currentId]
            );

            if ($row === null) {
                break;
            }

            array_unshift($chain, [
                'name' => (string) $row['name'],
                'slug' => (string) $row['slug'],
                'url'  => route('category', ['slug' => $row['slug']]),
            ]);

            $currentId = $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
            $guard++;
        }

        return $chain;
    }

    /**
     * Count published articles in a category, including all descendant
     * subcategories (recursive CTE).
     */
    public function getArticleCount(int $categoryId): int
    {
        $row = $this->db->fetch(
            "WITH RECURSIVE cat_tree AS (
                SELECT id FROM `categories` WHERE id = ?
                UNION ALL
                SELECT c.id FROM `categories` c
                INNER JOIN cat_tree ct ON c.parent_id = ct.id
             )
             SELECT COUNT(*) AS cnt
             FROM `articles`
             WHERE `category_id` IN (SELECT id FROM cat_tree)
               AND `status` = 'published'",
            [$categoryId]
        );

        return (int) ($row['cnt'] ?? 0);
    }
}
