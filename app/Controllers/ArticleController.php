<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Lang;
use App\Core\Session;
use App\Core\View;
use App\Models\ArticleModel;
use App\Models\BookmarkModel;
use App\Models\CommentModel;
use App\Models\LikeModel;
use App\Models\TagModel;

/**
 * Single-article display plus the AJAX like / bookmark / comment endpoints.
 */
final class ArticleController extends BaseController
{
    /**
     * GET /yazi/{cat}/{slug} — Turkish article page.
     */
    public function show(string $cat, string $slug): void
    {
        $this->render($cat, $slug, 'tr');
    }

    /**
     * GET /article/{cat}/{slug} — English article page.
     */
    public function showEn(string $cat, string $slug): void
    {
        $this->render($cat, $slug, 'en');
    }

    /**
     * Shared rendering for both languages.
     */
    private function render(string $cat, string $slug, string $lang): void
    {
        Lang::useLang($lang);

        $articles = new ArticleModel();
        $article  = $articles->findBySlug($slug, $lang);

        if ($article === null || ($article['status'] ?? '') !== 'published') {
            $this->notFound();

            return;
        }

        $articleId = (int) $article['id'];
        $articles->incrementViewCount($articleId);
        // Reflect the just-recorded view in the rendered figure.
        $article['view_count'] = (int) ($article['view_count'] ?? 0) + 1;

        // Defense-in-depth: content is sanitized on write, but re-clean on output
        // so any legacy/imported row can never inject script into readers/admins.
        $article['content'] = \App\Core\HtmlSanitizer::clean((string) ($article['content'] ?? ''));

        $related  = $articles->getRelated($articleId, (int) $article['category_id'], $lang, 6);
        $tags     = (new TagModel())->getForArticle($articleId, $lang);
        $comments = (new CommentModel())->getForArticle($articleId, true);

        $likeModel = new LikeModel();
        $likeCount = $likeModel->count($articleId);

        $userLiked = false;
        $userBookmarked = false;
        $user = Session::getUser();
        if ($user !== null) {
            $uid = (int) $user['id'];
            $userLiked = $likeModel->userLiked($uid, $articleId);
            $userBookmarked = (new BookmarkModel())->userBookmarked($uid, $articleId);
        }

        $catSeg = (string) $article['category_slug'];
        $urlTr = '/yazi/' . $catSeg . '/' . $slug;
        $urlEn = '/article/' . $catSeg . '/' . $slug;

        $metaTitle = (string) ($article['meta_title'] ?? '');
        if ($metaTitle === '') {
            $metaTitle = (string) $article['title'];
        }
        $metaDesc = (string) ($article['meta_description'] ?? '');
        if ($metaDesc === '') {
            $metaDesc = (string) ($article['excerpt'] ?? '');
        }

        View::render('article/show', [
            'lang'           => $lang,
            'article'        => $article,
            'related'        => $related,
            'tags'           => $tags,
            'comments'       => $comments,
            'likeCount'      => $likeCount,
            'userLiked'      => $userLiked,
            'userBookmarked' => $userBookmarked,
            'isLoggedIn'     => $user !== null,
            'meta'           => [
                'title'       => $metaTitle,
                'description' => $metaDesc,
                'og_image'    => $article['cover_image'] ?? null,
                'og_type'     => 'article',
                'url_tr'      => $urlTr,
                'url_en'      => $urlEn,
            ],
        ]);
    }

    /**
     * POST /yazi/{slug}/begen — toggle a like (JSON).
     */
    public function toggleLike(string $slug): void
    {
        $article = $this->requireArticleJson($slug);
        $userId  = $this->requireLoginJson();

        if (!$this->csrfOkJson()) {
            return;
        }

        $articleId = (int) $article['id'];
        $liked = (new LikeModel())->toggle($userId, $articleId);
        $count = (new LikeModel())->count($articleId);

        View::json(['liked' => $liked, 'count' => $count]);
    }

    /**
     * POST /yazi/{slug}/kaydet — toggle a bookmark (JSON).
     */
    public function toggleBookmark(string $slug): void
    {
        $article = $this->requireArticleJson($slug);
        $userId  = $this->requireLoginJson();

        if (!$this->csrfOkJson()) {
            return;
        }

        $bookmarked = (new BookmarkModel())->toggle($userId, (int) $article['id']);

        View::json(['bookmarked' => $bookmarked]);
    }

    /**
     * POST /yazi/{slug}/yorum — submit a comment for moderation (JSON).
     */
    public function submitComment(string $slug): void
    {
        $article = $this->requireArticleJson($slug);
        $userId  = $this->requireLoginJson();

        if (!$this->csrfOkJson()) {
            return;
        }

        $raw = (string) ($_POST['content'] ?? '');
        $content = trim(strip_tags($raw));

        if ($content === '' || mb_strlen($content) < 2) {
            View::json(['success' => false, 'message' => __('comment_empty')], 422);

            return;
        }
        if (mb_strlen($content) > 5000) {
            $content = mb_substr($content, 0, 5000);
        }

        $parentId = null;
        $parentRaw = (int) ($_POST['parent_id'] ?? 0);
        if ($parentRaw > 0) {
            $parentId = $parentRaw;
        }

        $comment = new CommentModel();
        $comment->insert([
            'article_id'  => (int) $article['id'],
            'user_id'     => $userId,
            'parent_id'   => $parentId,
            'content'     => $content,
            'is_approved' => 0,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        View::json(['success' => true, 'message' => __('comment_pending')]);
    }

    // ------------------------------------------------------------------
    // JSON-endpoint helpers
    // ------------------------------------------------------------------

    /**
     * Resolve the article for a JSON endpoint or emit a 404 JSON body + exit.
     *
     * @return array<string,mixed>
     */
    private function requireArticleJson(string $slug): array
    {
        $article = (new ArticleModel())->findBySlug($slug, Lang::getLang());
        if ($article === null || ($article['status'] ?? '') !== 'published') {
            View::json(['error' => 'not_found'], 404);
        }

        /** @var array<string,mixed> $article */
        return $article;
    }

    /**
     * Ensure a logged-in user for a JSON endpoint or emit 401 JSON + exit.
     */
    private function requireLoginJson(): int
    {
        $user = Session::getUser();
        if ($user === null) {
            View::json(['error' => 'login_required', 'message' => __('login_to_comment')], 401);
        }

        /** @var array<string,mixed> $user */
        return (int) $user['id'];
    }

    /**
     * Validate the CSRF token from POST body or X-CSRF-Token header.
     * Emits a 403 JSON body and returns false when invalid.
     */
    private function csrfOkJson(): bool
    {
        $token = (string) ($_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        if (!Session::validateToken($token)) {
            View::json(['error' => 'invalid_csrf'], 403);

            return false;
        }

        return true;
    }

    /**
     * Render the shared 404 page.
     */
    private function notFound(): void
    {
        http_response_code(404);
        View::render('errors/404', [
            'meta' => ['title' => __('error_404_title'), 'description' => ''],
        ]);
    }
}
