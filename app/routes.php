<?php

declare(strict_types=1);

/**
 * Application route table for My Blog.
 *
 * Required by public/index.php with the active App\Core\Router instance in
 * scope as $router. Every controller action reachable over HTTP is registered
 * here. Turkish slugs are canonical; English mirror routes point at the *En
 * handler variants where they exist, or the same lang-agnostic handler.
 *
 * Access control is enforced twice for defence in depth: a middleware attached
 * to the /yazar-paneli/* and /admin/* route groups here, PLUS the per-action
 * requireApprovedAuthor()/requireAdmin() guards inside the controllers.
 *
 * @var \App\Core\Router $router
 */

use App\Controllers\Admin\AdminArticleController;
use App\Controllers\Admin\AdminCategoryController;
use App\Controllers\Admin\AdminCommentController;
use App\Controllers\Admin\AdminDashboardController;
use App\Controllers\Admin\AdminSettingController;
use App\Controllers\Admin\AdminTagController;
use App\Controllers\Admin\AdminUserController;
use App\Controllers\ArticleController;
use App\Controllers\AuthController;
use App\Controllers\AuthorDashboardController;
use App\Controllers\CategoryController;
use App\Controllers\HomeController;
use App\Controllers\PageController;
use App\Controllers\ProfileController;
use App\Controllers\SearchController;
use App\Controllers\SeoController;
use App\Core\Auth;
use App\Core\Lang;
use App\Core\Session;
use App\Core\View;

/** @var \App\Core\Router $router */

// ---------------------------------------------------------------------------
// Middleware
// ---------------------------------------------------------------------------

/**
 * Require an authenticated, approved author (admins qualify). Sends a response
 * and returns false to short-circuit dispatch when the check fails.
 */
$approvedAuthorMiddleware = static function (array $params): bool {
    $auth = new Auth();

    if (!$auth->isLoggedIn()) {
        Session::set('_intended', $_SERVER['REQUEST_URI'] ?? '/');
        Session::setFlash('error', __('flash_login_required'));
        View::redirect('/giris');

        return false;
    }

    if (!$auth->isApprovedAuthor()) {
        Session::setFlash('error', __('flash_author_not_approved'));
        View::redirect('/');

        return false;
    }

    return true;
};

/**
 * Require an admin session. Unauthenticated visitors are sent to login;
 * authenticated non-admins get a 403.
 */
$adminMiddleware = static function (array $params): bool {
    $auth = new Auth();

    if ($auth->isAdmin()) {
        return true;
    }

    if (!$auth->isLoggedIn()) {
        Session::set('_intended', $_SERVER['REQUEST_URI'] ?? '/');
        Session::setFlash('error', __('flash_login_required'));
        View::redirect('/giris');

        return false;
    }

    if (!headers_sent()) {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!doctype html><meta charset="utf-8"><title>403</title><h1>403</h1><p>'
        . e(__('error_forbidden')) . '</p>';

    return false;
};

// ---------------------------------------------------------------------------
// Public site
// ---------------------------------------------------------------------------

$router->get('/', [HomeController::class, 'index'], 'home');
$router->get('/en', [HomeController::class, 'indexEn'], 'home_en');

// Search (registered before the article/category catch-alls; distinct prefixes).
$router->get('/ara', [SearchController::class, 'index'], 'search');
$router->get('/search', [SearchController::class, 'indexEn'], 'search_en');

// Static pages.
$router->get('/hakkimizda', [PageController::class, 'about'], 'about');
$router->get('/about', [PageController::class, 'about'], 'about_en');
$router->get('/iletisim', [PageController::class, 'contact'], 'contact');
$router->post('/iletisim', [PageController::class, 'sendContact'], 'contact_send');
$router->get('/contact', [PageController::class, 'contact'], 'contact_en');
$router->post('/contact', [PageController::class, 'sendContact'], 'contact_send_en');

// Public author profile.
$router->get('/yazar/{username}', [ProfileController::class, 'show'], 'profile');

// Article JSON endpoints (before the article show route so the more specific
// two-segment /yazi/{slug}/{action} patterns are matched first).
$router->post('/yazi/{slug}/begen', [ArticleController::class, 'toggleLike'], 'article_like');
$router->post('/yazi/{slug}/kaydet', [ArticleController::class, 'toggleBookmark'], 'article_bookmark');
$router->post('/yazi/{slug}/yorum', [ArticleController::class, 'submitComment'], 'article_comment');

// Article pages.
$router->get('/yazi/{cat}/{slug}', [ArticleController::class, 'show'], 'article');
$router->get('/article/{cat}/{slug}', [ArticleController::class, 'showEn'], 'article_en');

// Category pages — catch-all slug allows nested paths (teknoloji/yazilim).
$router->get('/kategori/{slug:.+}', [CategoryController::class, 'show'], 'category');
$router->get('/category/{slug:.+}', [CategoryController::class, 'showEn'], 'category_en');

// ---------------------------------------------------------------------------
// Authentication
// ---------------------------------------------------------------------------

$router->get('/giris', [AuthController::class, 'loginForm'], 'login');
$router->post('/giris', [AuthController::class, 'login'], 'login_submit');
$router->get('/kayit', [AuthController::class, 'registerForm'], 'register');
$router->post('/kayit', [AuthController::class, 'register'], 'register_submit');
$router->get('/cikis', [AuthController::class, 'logout'], 'logout');

// Google OAuth 2.0. Callback path matches the configured redirect URI.
$router->get('/auth/google', [AuthController::class, 'googleRedirect'], 'google_redirect');
$router->get('/auth/google/callback', [AuthController::class, 'googleCallback'], 'google_callback');

// Password reset lifecycle.
$router->get('/sifre-sifirla', [AuthController::class, 'forgotForm'], 'forgot');
$router->post('/sifre-sifirla', [AuthController::class, 'sendReset'], 'forgot_submit');
$router->get('/sifre-yenile/{token}', [AuthController::class, 'resetForm'], 'reset');
$router->post('/sifre-yenile/{token}', [AuthController::class, 'resetPassword'], 'reset_submit');

// ---------------------------------------------------------------------------
// Language switcher (AJAX; accepts JSON body or form POST + header/body CSRF).
// ---------------------------------------------------------------------------

$router->post('/lang', static function (): void {
    $raw  = file_get_contents('php://input');
    $json = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
    $json = is_array($json) ? $json : [];

    $token = (string) (
        $_POST['_csrf']
        ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($json['_csrf'] ?? ''))
    );

    if (!Session::validateToken($token)) {
        View::json(['ok' => false, 'error' => 'invalid_csrf'], 403);
    }

    $lang = (string) ($_POST['lang'] ?? ($json['lang'] ?? 'tr'));
    if (!in_array($lang, ['tr', 'en'], true)) {
        $lang = 'tr';
    }

    Lang::setLang($lang);
    View::json(['ok' => true, 'lang' => $lang]);
}, 'lang_switch');

// ---------------------------------------------------------------------------
// Author panel  (auth + approved-author middleware on the whole group)
// ---------------------------------------------------------------------------

$router->get('/yazar-paneli', [AuthorDashboardController::class, 'index'], 'author_dashboard', [$approvedAuthorMiddleware]);
$router->get('/yazar-paneli/yeni', [AuthorDashboardController::class, 'create'], 'author_create', [$approvedAuthorMiddleware]);
$router->post('/yazar-paneli/yeni', [AuthorDashboardController::class, 'store'], 'author_store', [$approvedAuthorMiddleware]);
$router->get('/yazar-paneli/duzenle/{id:\d+}', [AuthorDashboardController::class, 'edit'], 'author_edit', [$approvedAuthorMiddleware]);
$router->post('/yazar-paneli/duzenle/{id:\d+}', [AuthorDashboardController::class, 'update'], 'author_update', [$approvedAuthorMiddleware]);
$router->post('/yazar-paneli/gonder/{id:\d+}', [AuthorDashboardController::class, 'submitForReview'], 'author_submit', [$approvedAuthorMiddleware]);
$router->post('/yazar-paneli/sil/{id:\d+}', [AuthorDashboardController::class, 'delete'], 'author_delete', [$approvedAuthorMiddleware]);

// ---------------------------------------------------------------------------
// Admin panel  (admin middleware on the whole group)
// ---------------------------------------------------------------------------

$router->get('/admin', [AdminDashboardController::class, 'index'], 'admin_dashboard', [$adminMiddleware]);

// Articles.
$router->get('/admin/yazilar', [AdminArticleController::class, 'list'], 'admin_articles', [$adminMiddleware]);
$router->get('/admin/yazilar/{id:\d+}/duzenle', [AdminArticleController::class, 'edit'], 'admin_article_edit', [$adminMiddleware]);
$router->post('/admin/yazilar/{id:\d+}/duzenle', [AdminArticleController::class, 'update'], 'admin_article_update', [$adminMiddleware]);
$router->post('/admin/yazilar/{id:\d+}/onayla', [AdminArticleController::class, 'publish'], 'admin_article_publish', [$adminMiddleware]);
$router->post('/admin/yazilar/{id:\d+}/reddet', [AdminArticleController::class, 'reject'], 'admin_article_reject', [$adminMiddleware]);
$router->post('/admin/yazilar/{id:\d+}/sil', [AdminArticleController::class, 'delete'], 'admin_article_delete', [$adminMiddleware]);

// Categories.
$router->get('/admin/kategoriler', [AdminCategoryController::class, 'list'], 'admin_categories', [$adminMiddleware]);
$router->get('/admin/kategoriler/yeni', [AdminCategoryController::class, 'create'], 'admin_category_new', [$adminMiddleware]);
$router->post('/admin/kategoriler/yeni', [AdminCategoryController::class, 'store'], 'admin_category_store', [$adminMiddleware]);
$router->get('/admin/kategoriler/{id:\d+}', [AdminCategoryController::class, 'edit'], 'admin_category_edit', [$adminMiddleware]);
$router->post('/admin/kategoriler/{id:\d+}', [AdminCategoryController::class, 'update'], 'admin_category_update', [$adminMiddleware]);
$router->post('/admin/kategoriler/{id:\d+}/sil', [AdminCategoryController::class, 'delete'], 'admin_category_delete', [$adminMiddleware]);

// Users.
$router->get('/admin/kullanicilar', [AdminUserController::class, 'list'], 'admin_users', [$adminMiddleware]);
$router->post('/admin/kullanicilar/{id:\d+}/onayla', [AdminUserController::class, 'approveUser'], 'admin_user_approve', [$adminMiddleware]);
$router->post('/admin/kullanicilar/{id:\d+}/rol', [AdminUserController::class, 'setRole'], 'admin_user_role', [$adminMiddleware]);
$router->post('/admin/kullanicilar/{id:\d+}/ban', [AdminUserController::class, 'banUser'], 'admin_user_ban', [$adminMiddleware]);
$router->post('/admin/kullanicilar/{id:\d+}/sil', [AdminUserController::class, 'delete'], 'admin_user_delete', [$adminMiddleware]);

// Comments.
$router->get('/admin/yorumlar', [AdminCommentController::class, 'list'], 'admin_comments', [$adminMiddleware]);
$router->post('/admin/yorumlar/{id:\d+}/onayla', [AdminCommentController::class, 'approve'], 'admin_comment_approve', [$adminMiddleware]);
$router->post('/admin/yorumlar/{id:\d+}/reddet', [AdminCommentController::class, 'reject'], 'admin_comment_reject', [$adminMiddleware]);
$router->post('/admin/yorumlar/{id:\d+}/sil', [AdminCommentController::class, 'delete'], 'admin_comment_delete', [$adminMiddleware]);

// Tags.
$router->get('/admin/etiketler', [AdminTagController::class, 'list'], 'admin_tags', [$adminMiddleware]);
$router->post('/admin/etiketler/yeni', [AdminTagController::class, 'store'], 'admin_tag_store', [$adminMiddleware]);
$router->post('/admin/etiketler/{id:\d+}/sil', [AdminTagController::class, 'delete'], 'admin_tag_delete', [$adminMiddleware]);
$router->post('/admin/etiketler/{id:\d+}', [AdminTagController::class, 'update'], 'admin_tag_update', [$adminMiddleware]);

// Settings.
$router->get('/admin/ayarlar', [AdminSettingController::class, 'index'], 'admin_settings', [$adminMiddleware]);
$router->post('/admin/ayarlar', [AdminSettingController::class, 'save'], 'admin_settings_save', [$adminMiddleware]);

// ---------------------------------------------------------------------------
// SEO endpoints (stream their own body + content-type, bypass the layout).
// ---------------------------------------------------------------------------

$router->get('/sitemap.xml', [SeoController::class, 'sitemap'], 'sitemap');
$router->get('/robots.txt', [SeoController::class, 'robots'], 'robots');

// ---------------------------------------------------------------------------
// Fallback handlers
// ---------------------------------------------------------------------------

$router->setNotFoundHandler(static function (): void {
    http_response_code(404);
    View::render('errors/404', [
        'lang' => Lang::getLang(),
        'meta' => [
            'title'       => __('error_404_title'),
            'description' => '',
            'og_type'     => 'website',
        ],
    ]);
});

$router->setMethodNotAllowedHandler(static function (): void {
    http_response_code(405);
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!doctype html><meta charset="utf-8"><title>405</title><h1>405 Method Not Allowed</h1>';
});
