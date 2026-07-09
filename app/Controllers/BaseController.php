<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Pagination;
use App\Core\Session;
use App\Core\View;

/**
 * Shared base for every controller.
 *
 * Wraps App\Core\View for rendering/redirect/JSON, exposes the common access
 * guards (login / admin / approved-author), CSRF validation and a pagination
 * factory driven by the ?page query parameter. All output is produced through
 * these helpers so behaviour stays consistent across the app.
 */
abstract class BaseController
{
    private ?Auth $authService = null;

    /**
     * Lazily construct and reuse the auth service.
     */
    protected function auth(): Auth
    {
        if ($this->authService === null) {
            $this->authService = new Auth();
        }

        return $this->authService;
    }

    // ------------------------------------------------------------------
    // View helpers (thin wrappers around Core\View)
    // ------------------------------------------------------------------

    /**
     * Render a template inside a layout and emit it.
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $template, array $data = [], string $layout = 'main'): void
    {
        View::render($template, $data, $layout);
    }

    protected function redirect(string $url, int $status = 302): void
    {
        View::redirect($url, $status);
    }

    /**
     * @param mixed $data
     */
    protected function json(mixed $data, int $status = 200): void
    {
        View::json($data, $status);
    }

    // ------------------------------------------------------------------
    // Access guards
    // ------------------------------------------------------------------

    /**
     * Require an authenticated session; otherwise flash and redirect to login.
     */
    protected function requireLogin(): void
    {
        if (!$this->auth()->isLoggedIn()) {
            Session::set('_intended', $_SERVER['REQUEST_URI'] ?? '/');
            Session::setFlash('error', __('flash_login_required'));
            $this->redirect('/giris');
        }
    }

    /**
     * Require an admin session; otherwise emit a 403 and stop.
     */
    protected function requireAdmin(): void
    {
        if (!$this->auth()->isAdmin()) {
            $this->abort403();
        }
    }

    /**
     * Require an approved author (or admin). Unauthenticated visitors are sent
     * to login; logged-in but unapproved users are informed and sent home.
     */
    protected function requireApprovedAuthor(): void
    {
        if (!$this->auth()->isLoggedIn()) {
            $this->requireLogin();

            return;
        }

        if (!$this->auth()->isApprovedAuthor()) {
            Session::setFlash('error', __('flash_author_not_approved'));
            $this->redirect('/');
        }
    }

    /**
     * Validate the POSTed CSRF token; abort with 403 when it is missing/invalid.
     */
    protected function validateCsrf(): void
    {
        $token = $_POST['_csrf'] ?? null;
        if (!is_string($token) || !Session::validateToken($token)) {
            $this->abort403(__('flash_csrf_invalid'));
        }
    }

    /**
     * Emit a 403 response and terminate.
     */
    protected function abort403(?string $message = null): void
    {
        if (!headers_sent()) {
            http_response_code(403);
            header('Content-Type: text/html; charset=utf-8');
        }
        $msg = $message ?? __('error_forbidden');
        echo '<!doctype html><html lang="' . e(\App\Core\Lang::getLang())
            . '"><head><meta charset="utf-8"><title>403</title></head><body>'
            . '<h1>403</h1><p>' . e($msg) . '</p></body></html>';
        exit;
    }

    // ------------------------------------------------------------------
    // Pagination
    // ------------------------------------------------------------------

    /**
     * Build a Pagination instance for the current request using ?page.
     */
    protected function paginate(int $total, int $perPage): Pagination
    {
        $baseUrl = $_SERVER['REQUEST_URI'] ?? '/';

        return new Pagination($total, $perPage, $this->currentPage(), $baseUrl);
    }

    /**
     * Current 1-based page number from the query string (never below 1).
     */
    protected function currentPage(): int
    {
        return max(1, (int) ($_GET['page'] ?? 1));
    }

    // ------------------------------------------------------------------
    // Request helpers
    // ------------------------------------------------------------------

    /**
     * Trimmed string value from $_POST (empty string when absent).
     */
    protected function input(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    /**
     * Best-effort client IP for rate limiting.
     */
    protected function clientIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        return is_string($ip) && $ip !== '' ? $ip : '0.0.0.0';
    }

    /**
     * Persist non-sensitive submitted fields so a form can be re-populated
     * after a validation-failure redirect.
     *
     * @param array<string, mixed> $input
     */
    protected function flashInput(array $input): void
    {
        unset($input['password'], $input['password_confirm'], $input['_csrf']);
        Session::set('_old', $input);
    }

    /**
     * Retrieve and clear the previously flashed input.
     *
     * @return array<string, mixed>
     */
    protected function oldInput(): array
    {
        $old = Session::get('_old', []);
        Session::remove('_old');

        return is_array($old) ? $old : [];
    }
}
