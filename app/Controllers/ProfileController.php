<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Lang;
use App\Models\ArticleModel;
use App\Models\SettingModel;
use App\Models\UserModel;

/**
 * Public author profile pages: /yazar/{username}.
 *
 * Only approved authors (and admins) have a visible public profile; anything
 * else yields a 404.
 */
final class ProfileController extends BaseController
{
    public function show(string $username): void
    {
        $users = new UserModel();
        $user  = $users->findByUsername($username);

        $isVisible = $user !== null
            && in_array($user['role'] ?? '', ['author', 'admin'], true)
            && (int) ($user['is_approved'] ?? 0) === 1;

        if (!$isVisible || $user === null) {
            $this->notFound();

            return;
        }

        $lang    = Lang::getLang();
        $perPage = (int) (SettingModel::get('articles_per_page', '12') ?? 12);
        $perPage = $perPage > 0 ? $perPage : 12;
        $page    = $this->currentPage();

        $userId   = (int) $user['id'];
        $articles = new ArticleModel();
        $total    = $articles->countByAuthor($userId, 'published');
        $rows     = $articles->getByAuthor($userId, 'published', $page, $perPage);
        $stats    = $users->getAuthorStats($userId);

        $this->view('profile/show', [
            'title'      => $user['name'] ?? $user['username'],
            'profile'    => $user,
            'articles'   => $rows,
            'stats'      => $stats,
            'pagination' => $this->paginate($total, $perPage),
        ]);
    }

    /**
     * Emit a minimal 404 response and stop.
     */
    private function notFound(): void
    {
        if (!headers_sent()) {
            http_response_code(404);
            header('Content-Type: text/html; charset=utf-8');
        }
        echo '<!doctype html><html lang="' . e(Lang::getLang())
            . '"><head><meta charset="utf-8"><title>404</title></head><body>'
            . '<h1>404</h1><p>' . e(__('error_not_found')) . '</p></body></html>';
        exit;
    }
}
