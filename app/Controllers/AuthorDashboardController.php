<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Image;
use App\Core\Lang;
use App\Core\Session;
use App\Models\ArticleModel;
use App\Models\CategoryModel;
use App\Models\SettingModel;
use App\Models\TagModel;
use App\Models\UserModel;
use RuntimeException;
use Throwable;

/**
 * Author dashboard: an author's own article CRUD and the editorial submission
 * flow. Every action requires an approved author (admins allowed).
 */
final class AuthorDashboardController extends BaseController
{
    // ------------------------------------------------------------------
    // Listing
    // ------------------------------------------------------------------

    public function index(): void
    {
        $this->requireApprovedAuthor();

        $authorId = (int) $this->auth()->id();
        $perPage  = $this->perPage();
        $page     = $this->currentPage();

        $articles = new ArticleModel();
        $total    = $articles->countByAuthor($authorId);
        $rows     = $articles->getByAuthor($authorId, null, $page, $perPage);

        $stats = (new UserModel())->getAuthorStats($authorId);

        $this->view('author/dashboard', [
            'title'      => __('dashboard_title'),
            'articles'   => $rows,
            'stats'      => $stats,
            'pagination' => $this->paginate($total, $perPage),
        ], 'author');
    }

    // ------------------------------------------------------------------
    // Create
    // ------------------------------------------------------------------

    public function create(): void
    {
        $this->requireApprovedAuthor();

        $this->view('author/editor', [
            'title'      => __('editor_new_title'),
            'mode'       => 'create',
            'formAction' => '/yazar-paneli/yeni',
            'article'    => $this->emptyArticle(),
            'tagsString' => '',
            'categories' => $this->categoryOptions(),
        ], 'author');
    }

    public function store(): void
    {
        $this->requireApprovedAuthor();
        $this->validateCsrf();

        $data   = $this->collectArticleInput();
        $errors = $this->validateArticleInput($data);

        if ($errors !== []) {
            foreach ($errors as $error) {
                Session::setFlash('error', $error);
            }
            $this->redirect('/yazar-paneli/yeni');

            return;
        }

        $coverPath = $this->handleCoverUpload();
        if ($coverPath === false) {
            $this->redirect('/yazar-paneli/yeni');

            return;
        }

        $db       = Database::getInstance();
        $authorId = (int) $this->auth()->id();
        $slug     = $this->uniqueSlug($this->slugify($data['title']));

        $db->beginTransaction();
        try {
            $db->execute(
                'INSERT INTO articles
                    (author_id, category_id, slug, cover_image, status, lang, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
                [$authorId, $data['category_id'], $slug, $coverPath, 'draft', $data['lang']]
            );
            $articleId = $db->lastInsertId();

            $this->upsertTranslation($articleId, $data);
            $this->syncTags($articleId, $data['lang'], $data['tags']);

            $db->commit();
        } catch (Throwable $e) {
            $db->rollback();
            if ($coverPath !== null) {
                Image::delete($coverPath);
            }
            throw $e;
        }

        Session::setFlash('success', __('flash_article_created'));
        $this->redirect('/yazar-paneli');
    }

    // ------------------------------------------------------------------
    // Edit
    // ------------------------------------------------------------------

    public function edit(string $id): void
    {
        $this->requireApprovedAuthor();

        $article = $this->loadOwnArticle((int) $id);
        if ($article === null) {
            return;
        }

        $lang    = $article['lang'] ?? Lang::getLang();
        $details = (new ArticleModel())->getWithDetails((int) $article['id'], (string) $lang) ?? $article;

        $tags = (new TagModel())->getForArticle((int) $article['id'], (string) $lang);
        $tagsString = implode(', ', array_map(static fn (array $t): string => (string) $t['name'], $tags));

        $this->view('author/editor', [
            'title'      => __('editor_edit_title'),
            'mode'       => 'edit',
            'formAction' => '/yazar-paneli/duzenle/' . (int) $article['id'],
            'article'    => $details,
            'tagsString' => $tagsString,
            'categories' => $this->categoryOptions(),
        ], 'author');
    }

    public function update(string $id): void
    {
        $this->requireApprovedAuthor();
        $this->validateCsrf();

        $article = $this->loadOwnArticle((int) $id);
        if ($article === null) {
            return;
        }

        $data   = $this->collectArticleInput();
        $errors = $this->validateArticleInput($data);

        if ($errors !== []) {
            foreach ($errors as $error) {
                Session::setFlash('error', $error);
            }
            $this->redirect('/yazar-paneli/duzenle/' . (int) $article['id']);

            return;
        }

        $articleId = (int) $article['id'];
        $coverPath = $this->handleCoverUpload();
        if ($coverPath === false) {
            $this->redirect('/yazar-paneli/duzenle/' . $articleId);

            return;
        }
        $db        = Database::getInstance();

        $db->beginTransaction();
        try {
            if ($coverPath !== null) {
                $db->execute(
                    'UPDATE articles SET category_id = ?, lang = ?, cover_image = ?, updated_at = NOW() WHERE id = ?',
                    [$data['category_id'], $data['lang'], $coverPath, $articleId]
                );
            } else {
                $db->execute(
                    'UPDATE articles SET category_id = ?, lang = ?, updated_at = NOW() WHERE id = ?',
                    [$data['category_id'], $data['lang'], $articleId]
                );
            }

            $this->upsertTranslation($articleId, $data);
            $this->syncTags($articleId, $data['lang'], $data['tags']);

            $db->commit();
        } catch (Throwable $e) {
            $db->rollback();
            if ($coverPath !== null) {
                Image::delete($coverPath);
            }
            throw $e;
        }

        // Remove the previous cover only after a successful swap.
        if ($coverPath !== null && !empty($article['cover_image'])) {
            Image::delete((string) $article['cover_image']);
        }

        Session::setFlash('success', __('flash_article_updated'));
        $this->redirect('/yazar-paneli');
    }

    // ------------------------------------------------------------------
    // Delete / submit
    // ------------------------------------------------------------------

    public function delete(string $id): void
    {
        $this->requireApprovedAuthor();
        $this->validateCsrf();

        $article = $this->loadOwnArticle((int) $id);
        if ($article === null) {
            return;
        }

        if (($article['status'] ?? '') !== 'draft') {
            Session::setFlash('error', __('flash_article_delete_not_draft'));
            $this->redirect('/yazar-paneli');

            return;
        }

        $cover = $article['cover_image'] ?? null;
        (new ArticleModel())->delete((int) $article['id']);
        if (!empty($cover)) {
            Image::delete((string) $cover);
        }

        Session::setFlash('success', __('flash_article_deleted'));
        $this->redirect('/yazar-paneli');
    }

    public function submitForReview(string $id): void
    {
        $this->requireApprovedAuthor();
        $this->validateCsrf();

        $article = $this->loadOwnArticle((int) $id);
        if ($article === null) {
            return;
        }

        if (!in_array($article['status'] ?? '', ['draft', 'rejected'], true)) {
            Session::setFlash('error', __('flash_article_submit_invalid'));
            $this->redirect('/yazar-paneli');

            return;
        }

        (new ArticleModel())->submitForReview((int) $article['id']);
        Session::setFlash('success', __('flash_article_submitted'));
        $this->redirect('/yazar-paneli');
    }

    // ------------------------------------------------------------------
    // Input handling
    // ------------------------------------------------------------------

    /**
     * @return array{
     *   category_id:int, lang:string, title:string, content:string,
     *   excerpt:string, meta_title:string, meta_description:string, tags:array<int,string>
     * }
     */
    private function collectArticleInput(): array
    {
        $lang = $this->input('lang');
        if (!in_array($lang, ['tr', 'en'], true)) {
            $lang = 'tr';
        }

        $tagsRaw = $this->input('tags');
        $tags    = [];
        foreach (explode(',', $tagsRaw) as $name) {
            $name = trim($name);
            if ($name !== '') {
                $tags[] = $name;
            }
        }

        return [
            'category_id'      => (int) ($_POST['category_id'] ?? 0),
            'lang'             => $lang,
            'title'            => $this->input('title'),
            'content'          => \App\Core\HtmlSanitizer::clean((string) ($_POST['content'] ?? '')),
            'excerpt'          => $this->input('excerpt'),
            'meta_title'       => $this->input('meta_title'),
            'meta_description' => $this->input('meta_description'),
            'tags'             => $tags,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validateArticleInput(array $data): array
    {
        $errors = [];

        if (($data['title'] ?? '') === '' || mb_strlen((string) $data['title']) > 500) {
            $errors[] = __('validation_article_title');
        }
        if (trim(strip_tags((string) ($data['content'] ?? ''))) === '') {
            $errors[] = __('validation_article_content');
        }

        $categoryId = (int) ($data['category_id'] ?? 0);
        if ($categoryId <= 0 || (new CategoryModel())->find($categoryId) === null) {
            $errors[] = __('validation_article_category');
        }

        if (mb_strlen((string) ($data['meta_title'] ?? '')) > 160) {
            $errors[] = __('validation_meta_title');
        }
        if (mb_strlen((string) ($data['meta_description'] ?? '')) > 320) {
            $errors[] = __('validation_meta_description');
        }

        return $errors;
    }

    /**
     * Upsert the article translation row for the given language.
     *
     * @param array<string, mixed> $data
     */
    private function upsertTranslation(int $articleId, array $data): void
    {
        Database::getInstance()->execute(
            'INSERT INTO article_translations
                (article_id, lang, title, content, excerpt, meta_title, meta_description)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                content = VALUES(content),
                excerpt = VALUES(excerpt),
                meta_title = VALUES(meta_title),
                meta_description = VALUES(meta_description)',
            [
                $articleId,
                $data['lang'],
                $data['title'],
                $data['content'],
                $data['excerpt'] !== '' ? $data['excerpt'] : null,
                $data['meta_title'] !== '' ? $data['meta_title'] : null,
                $data['meta_description'] !== '' ? $data['meta_description'] : null,
            ]
        );
    }

    /**
     * Resolve tag names (creating tags/translations as needed) and attach them.
     *
     * @param array<int, string> $names
     */
    private function syncTags(int $articleId, string $lang, array $names): void
    {
        $db     = Database::getInstance();
        $tagIds = [];

        foreach ($names as $name) {
            $slug = $this->slugify($name);
            if ($slug === '') {
                continue;
            }

            $existing = $db->fetch('SELECT id FROM tags WHERE slug = ? LIMIT 1', [$slug]);
            if ($existing !== null) {
                $tagId = (int) $existing['id'];
            } else {
                $db->execute('INSERT INTO tags (slug, created_at) VALUES (?, NOW())', [$slug]);
                $tagId = $db->lastInsertId();
            }

            // Ensure a translation exists for the article language.
            $db->execute(
                'INSERT INTO tag_translations (tag_id, lang, name) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE name = VALUES(name)',
                [$tagId, $lang, $name]
            );

            $tagIds[] = $tagId;
        }

        (new TagModel())->syncArticleTags($articleId, $tagIds);
    }

    /**
     * Upload a cover image when one was provided; return its public path or null.
     */
    /**
     * @return string|false|null path on success, null when no file was provided,
     *                           false when an upload was attempted but failed
     *                           (an error flash is set; the caller must abort).
     */
    private function handleCoverUpload(): string|false|null
    {
        if (!isset($_FILES['cover']) || !is_array($_FILES['cover'])) {
            return null;
        }
        if (($_FILES['cover']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        try {
            return Image::upload($_FILES['cover'], 'articles');
        } catch (RuntimeException $e) {
            Session::setFlash('error', __('flash_cover_upload_failed'));

            return false;
        }
    }

    // ------------------------------------------------------------------
    // Ownership & lookups
    // ------------------------------------------------------------------

    /**
     * Load an article the current user may manage, or emit a redirect/return null.
     *
     * @return array<string, mixed>|null
     */
    private function loadOwnArticle(int $id): ?array
    {
        $article = (new ArticleModel())->find($id);

        if ($article === null) {
            Session::setFlash('error', __('flash_article_not_found'));
            $this->redirect('/yazar-paneli');

            return null;
        }

        $ownerId = (int) ($article['author_id'] ?? 0);
        if ($ownerId !== (int) $this->auth()->id() && !$this->auth()->isAdmin()) {
            Session::setFlash('error', __('flash_article_forbidden'));
            $this->redirect('/yazar-paneli');

            return null;
        }

        return $article;
    }

    /**
     * Flattened, indented category list for the <select>.
     *
     * @return array<int, array{id:int, label:string}>
     */
    private function categoryOptions(): array
    {
        $tree = (new CategoryModel())->getTree(Lang::getLang());
        $flat = [];
        $this->flattenTree($tree, 0, $flat);

        return $flat;
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     * @param array<int, array{id:int, label:string}> $out
     */
    private function flattenTree(array $nodes, int $depth, array &$out): void
    {
        foreach ($nodes as $node) {
            $out[] = [
                'id'    => (int) $node['id'],
                'label' => str_repeat('— ', $depth) . (string) ($node['name'] ?? $node['slug']),
            ];
            if (!empty($node['children']) && is_array($node['children'])) {
                $this->flattenTree($node['children'], $depth + 1, $out);
            }
        }
    }

    private function perPage(): int
    {
        $perPage = (int) (SettingModel::get('articles_per_page', '12') ?? 12);

        return $perPage > 0 ? $perPage : 12;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyArticle(): array
    {
        return [
            'id'               => 0,
            'category_id'      => 0,
            'lang'             => Lang::getLang(),
            'title'            => '',
            'content'          => '',
            'excerpt'          => '',
            'meta_title'       => '',
            'meta_description' => '',
            'cover_image'      => null,
            'status'           => 'draft',
        ];
    }

    // ------------------------------------------------------------------
    // Slugs
    // ------------------------------------------------------------------

    /**
     * ASCII slug with Turkish transliteration.
     */
    private function slugify(string $value): string
    {
        $map = [
            'ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'İ' => 'i', 'ö' => 'o',
            'ş' => 's', 'ü' => 'u', 'Ç' => 'c', 'Ğ' => 'g', 'Ö' => 'o',
            'Ş' => 's', 'Ü' => 'u', 'I' => 'i',
        ];
        $value = strtr($value, $map);
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? '';

        return trim($value, '-');
    }

    /**
     * Guarantee slug uniqueness against the articles table.
     */
    private function uniqueSlug(string $slug): string
    {
        $slug = $slug !== '' ? $slug : 'yazi';
        $slug = substr($slug, 0, 280);

        $db        = Database::getInstance();
        $candidate = $slug;
        while ($db->fetch('SELECT id FROM articles WHERE slug = ? LIMIT 1', [$candidate]) !== null) {
            $candidate = $slug . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
        }

        return $candidate;
    }
}
