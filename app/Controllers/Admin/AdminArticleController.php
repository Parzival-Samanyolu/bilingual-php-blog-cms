<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Image;
use App\Core\Pagination;
use App\Core\Session;
use App\Core\View;
use App\Models\ArticleModel;
use App\Models\CategoryModel;
use App\Models\TagModel;
use RuntimeException;

/**
 * Admin management of articles: listing, editing (any author), moderation.
 */
class AdminArticleController extends BaseController
{
    private const PER_PAGE = 20;
    private const VALID_STATUS = ['draft', 'pending', 'published', 'rejected'];

    /**
     * GET /admin/yazilar — filterable, paginated article table.
     */
    public function list(): void
    {
        $this->requireAdmin();

        $db = Database::getInstance();

        $status   = (string) ($_GET['status'] ?? '');
        $category = ($_GET['category'] ?? '') !== '' ? (int) $_GET['category'] : null;
        $author   = ($_GET['author'] ?? '') !== '' ? (int) $_GET['author'] : null;
        $lang     = (string) ($_GET['lang'] ?? '');
        $page     = max(1, (int) ($_GET['page'] ?? 1));

        $where  = [];
        $params = [];

        if (in_array($status, self::VALID_STATUS, true)) {
            $where[]  = 'a.status = ?';
            $params[] = $status;
        }
        if ($category !== null) {
            $where[]  = 'a.category_id = ?';
            $params[] = $category;
        }
        if ($author !== null) {
            $where[]  = 'a.author_id = ?';
            $params[] = $author;
        }
        if (in_array($lang, ['tr', 'en'], true)) {
            $where[]  = 'a.lang = ?';
            $params[] = $lang;
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int) $db->fetchColumn(
            "SELECT COUNT(*) FROM `articles` a {$whereSql}",
            $params
        );

        $pagination = new Pagination($total, self::PER_PAGE, $page, $this->baseUrl());
        $offset = $pagination->getOffset();

        $sql = "SELECT a.id, a.slug, a.status, a.lang, a.view_count, a.created_at,
                       a.author_id, a.category_id,
                       COALESCE(t.title, a.slug) AS title,
                       u.name AS author_name, u.username AS author_username,
                       COALESCE(ct.name, ctb.name) AS category_name
                FROM `articles` a
                LEFT JOIN `article_translations` t
                    ON t.article_id = a.id AND t.lang = a.lang
                INNER JOIN `users` u ON u.id = a.author_id
                INNER JOIN `categories` c ON c.id = a.category_id
                LEFT JOIN `category_translations` ct
                    ON ct.category_id = c.id AND ct.lang = a.lang
                LEFT JOIN `category_translations` ctb
                    ON ctb.category_id = c.id AND ctb.lang = 'tr'
                {$whereSql}
                ORDER BY a.created_at DESC
                LIMIT " . self::PER_PAGE . " OFFSET " . (int) $offset;

        $articles = $db->fetchAll($sql, $params);

        View::render('admin/articles/list', [
            'pageTitle'   => __('admin_articles'),
            'articles'    => $articles,
            'pagination'  => $pagination,
            'categories'  => (new CategoryModel())->getTree('tr'),
            'authors'     => $this->authorOptions(),
            'filters'     => [
                'status'   => $status,
                'category' => $category,
                'author'   => $author,
                'lang'     => $lang,
            ],
        ], 'admin');
    }

    /**
     * GET /admin/yazilar/{id}/duzenle — edit form (Quill editor, author picker).
     */
    public function edit(int $id): void
    {
        $this->requireAdmin();

        $db = Database::getInstance();
        $article = $db->fetch("SELECT * FROM `articles` WHERE `id` = ? LIMIT 1", [$id]);

        if ($article === null) {
            Session::setFlash('error', __('admin_article_not_found'));
            View::redirect('/admin/yazilar');
            return;
        }

        $lang = (string) $article['lang'];
        $translation = $db->fetch(
            "SELECT * FROM `article_translations` WHERE `article_id` = ? AND `lang` = ? LIMIT 1",
            [$id, $lang]
        ) ?? [];

        $tags = (new TagModel())->getForArticle($id, $lang);
        $tagNames = array_map(static fn (array $t): string => (string) $t['name'], $tags);

        View::render('admin/articles/edit', [
            'pageTitle'   => __('admin_article_edit'),
            'article'     => $article,
            'translation' => $translation,
            'tagString'   => implode(', ', $tagNames),
            'categories'  => (new CategoryModel())->getTree('tr'),
            'authors'     => $this->authorOptions(),
        ], 'admin');
    }

    /**
     * POST /admin/yazilar/{id}/duzenle — persist edits.
     */
    public function update(int $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $db = Database::getInstance();
        $article = $db->fetch("SELECT * FROM `articles` WHERE `id` = ? LIMIT 1", [$id]);

        if ($article === null) {
            Session::setFlash('error', __('admin_article_not_found'));
            View::redirect('/admin/yazilar');
            return;
        }

        $title       = trim((string) ($_POST['title'] ?? ''));
        $content     = \App\Core\HtmlSanitizer::clean((string) ($_POST['content'] ?? ''));
        $excerpt     = trim((string) ($_POST['excerpt'] ?? ''));
        $metaTitle   = trim((string) ($_POST['meta_title'] ?? ''));
        $metaDesc    = trim((string) ($_POST['meta_description'] ?? ''));
        $categoryId  = (int) ($_POST['category_id'] ?? $article['category_id']);
        $authorId    = (int) ($_POST['author_id'] ?? $article['author_id']);
        $status      = (string) ($_POST['status'] ?? $article['status']);
        $lang        = in_array(($_POST['lang'] ?? ''), ['tr', 'en'], true)
            ? (string) $_POST['lang']
            : (string) $article['lang'];
        $slug        = trim((string) ($_POST['slug'] ?? ''));

        if (!in_array($status, self::VALID_STATUS, true)) {
            $status = (string) $article['status'];
        }

        if ($title === '') {
            Session::setFlash('error', __('admin_article_title_required'));
            View::redirect('/admin/yazilar/' . $id . '/duzenle');
            return;
        }

        $slug = $slug !== '' ? $this->slugify($slug) : $this->slugify($title);
        $slug = $this->uniqueSlug($slug, $id);

        // Cover image (optional new upload).
        $coverImage = $article['cover_image'];
        if (isset($_FILES['cover_image']) && ($_FILES['cover_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            try {
                $newCover = Image::upload($_FILES['cover_image'], 'articles');
                if (!empty($coverImage)) {
                    Image::delete((string) $coverImage);
                }
                $coverImage = $newCover;
            } catch (RuntimeException $e) {
                Session::setFlash('error', __('admin_image_upload_failed') . ' ' . $e->getMessage());
                View::redirect('/admin/yazilar/' . $id . '/duzenle');
                return;
            }
        }

        $db->beginTransaction();
        try {
            $db->execute(
                "UPDATE `articles`
                    SET `author_id` = ?, `category_id` = ?, `slug` = ?, `cover_image` = ?,
                        `status` = ?, `lang` = ?, `updated_at` = NOW()
                  WHERE `id` = ?",
                [$authorId, $categoryId, $slug, $coverImage !== null ? $coverImage : null, $status, $lang, $id]
            );

            $existing = $db->fetch(
                "SELECT id FROM `article_translations` WHERE `article_id` = ? AND `lang` = ? LIMIT 1",
                [$id, $lang]
            );

            if ($existing !== null) {
                $db->execute(
                    "UPDATE `article_translations`
                        SET `title` = ?, `content` = ?, `excerpt` = ?,
                            `meta_title` = ?, `meta_description` = ?
                      WHERE `article_id` = ? AND `lang` = ?",
                    [$title, $content, $excerpt ?: null, $metaTitle ?: null, $metaDesc ?: null, $id, $lang]
                );
            } else {
                $db->execute(
                    "INSERT INTO `article_translations`
                        (`article_id`, `lang`, `title`, `content`, `excerpt`, `meta_title`, `meta_description`)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$id, $lang, $title, $content, $excerpt ?: null, $metaTitle ?: null, $metaDesc ?: null]
                );
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            Session::setFlash('error', __('admin_save_failed'));
            View::redirect('/admin/yazilar/' . $id . '/duzenle');
            return;
        }

        // Tags (comma separated) — resolve/create then sync.
        $tagIds = $this->resolveTags((string) ($_POST['tags'] ?? ''), $lang);
        (new TagModel())->syncArticleTags($id, $tagIds);

        Session::setFlash('success', __('admin_article_saved'));
        View::redirect('/admin/yazilar/' . $id . '/duzenle');
    }

    /**
     * POST /admin/yazilar/{id}/onayla — publish.
     */
    public function publish(int $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        (new ArticleModel())->publish($id);
        Session::setFlash('success', __('admin_article_published'));
        View::redirect($this->backTo('/admin/yazilar'));
    }

    /**
     * POST /admin/yazilar/{id}/reddet — reject (optional reason, not persisted).
     */
    public function reject(int $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $reason = trim((string) ($_POST['reason'] ?? '')) ?: null;
        (new ArticleModel())->reject($id, $reason);
        Session::setFlash('success', __('admin_article_rejected'));
        View::redirect($this->backTo('/admin/yazilar'));
    }

    /**
     * POST /admin/yazilar/{id}/sil — delete article (and cover file).
     */
    public function delete(int $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $db = Database::getInstance();
        $article = $db->fetch("SELECT `cover_image` FROM `articles` WHERE `id` = ? LIMIT 1", [$id]);

        (new ArticleModel())->delete($id);

        if ($article !== null && !empty($article['cover_image'])) {
            Image::delete((string) $article['cover_image']);
        }

        Session::setFlash('success', __('admin_article_deleted'));
        View::redirect($this->backTo('/admin/yazilar'));
    }

    /**
     * Author/admin users available as article authors.
     *
     * @return array<int,array<string,mixed>>
     */
    private function authorOptions(): array
    {
        return Database::getInstance()->fetchAll(
            "SELECT `id`, `name`, `username` FROM `users`
             WHERE `role` IN ('admin', 'author')
             ORDER BY `name` ASC"
        );
    }

    /**
     * Resolve a comma-separated tag string to tag ids, creating missing tags.
     *
     * @return array<int,int>
     */
    private function resolveTags(string $raw, string $lang): array
    {
        $names = array_filter(array_map('trim', explode(',', $raw)), static fn ($n): bool => $n !== '');
        if ($names === []) {
            return [];
        }

        $db = Database::getInstance();
        $ids = [];

        foreach ($names as $name) {
            $row = $db->fetch(
                "SELECT `tag_id` FROM `tag_translations` WHERE LOWER(`name`) = LOWER(?) LIMIT 1",
                [$name]
            );
            if ($row !== null) {
                $ids[] = (int) $row['tag_id'];
                continue;
            }

            $slug = $this->uniqueTagSlug($this->slugify($name));
            $db->execute("INSERT INTO `tags` (`slug`, `created_at`) VALUES (?, NOW())", [$slug]);
            $tagId = $db->lastInsertId();

            foreach (['tr', 'en'] as $l) {
                $db->execute(
                    "INSERT INTO `tag_translations` (`tag_id`, `lang`, `name`) VALUES (?, ?, ?)",
                    [$tagId, $l, $name]
                );
            }
            $ids[] = $tagId;
        }

        return array_values(array_unique($ids));
    }

    private function uniqueTagSlug(string $slug): string
    {
        $db = Database::getInstance();
        $base = $slug;
        $i = 2;
        while ((int) $db->fetchColumn("SELECT COUNT(*) FROM `tags` WHERE `slug` = ?", [$slug]) > 0) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    private function uniqueSlug(string $slug, int $exceptId): string
    {
        $db = Database::getInstance();
        $base = $slug;
        $i = 2;
        while (
            (int) $db->fetchColumn(
                "SELECT COUNT(*) FROM `articles` WHERE `slug` = ? AND `id` <> ?",
                [$slug, $exceptId]
            ) > 0
        ) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    private function slugify(string $text): string
    {
        $text = str_replace(
            ['ç', 'ğ', 'ı', 'i̇', 'İ', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'Ö', 'Ş', 'Ü'],
            ['c', 'g', 'i', 'i', 'i', 'o', 's', 'u', 'c', 'g', 'o', 's', 'u'],
            $text
        );
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        $text = trim($text, '-');

        return $text !== '' ? $text : 'yazi-' . substr(md5(uniqid('', true)), 0, 8);
    }

    private function baseUrl(): string
    {
        $query = $_GET;
        unset($query['page']);
        $qs = http_build_query($query);

        return '/admin/yazilar' . ($qs !== '' ? '?' . $qs : '');
    }

    private function backTo(string $fallback): string
    {
        $ref = (string) ($_POST['redirect'] ?? '');
        return $ref !== '' && str_starts_with($ref, '/admin') ? $ref : $fallback;
    }
}
