<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Pagination;
use App\Core\Session;
use App\Core\View;
use App\Models\CommentModel;

/**
 * Admin moderation of comments.
 */
class AdminCommentController extends BaseController
{
    private const PER_PAGE = 30;

    /**
     * GET /admin/yorumlar — moderation list, filterable by approval state.
     */
    public function list(): void
    {
        $this->requireAdmin();

        $db = Database::getInstance();

        $isApproved = ($_GET['is_approved'] ?? '') !== '' ? (int) $_GET['is_approved'] : null;
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $where  = [];
        $params = [];
        if ($isApproved !== null && in_array($isApproved, [0, 1], true)) {
            $where[]  = 'cm.is_approved = ?';
            $params[] = $isApproved;
        }
        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int) $db->fetchColumn(
            "SELECT COUNT(*) FROM `comments` cm {$whereSql}",
            $params
        );

        $pagination = new Pagination($total, self::PER_PAGE, $page, $this->baseUrl());
        $offset = $pagination->getOffset();

        $comments = $db->fetchAll(
            "SELECT cm.id, cm.content, cm.is_approved, cm.created_at, cm.parent_id,
                    u.name AS user_name, u.username AS user_username,
                    a.slug AS article_slug,
                    COALESCE(t.title, tb.title, a.slug) AS article_title
             FROM `comments` cm
             INNER JOIN `users` u ON u.id = cm.user_id
             INNER JOIN `articles` a ON a.id = cm.article_id
             LEFT JOIN `article_translations` t  ON t.article_id = a.id AND t.lang = a.lang
             LEFT JOIN `article_translations` tb ON tb.article_id = a.id AND tb.lang = 'tr'
             {$whereSql}
             ORDER BY cm.created_at DESC
             LIMIT " . self::PER_PAGE . " OFFSET " . (int) $offset,
            $params
        );

        View::render('admin/comments/list', [
            'pageTitle'  => __('admin_comments'),
            'comments'   => $comments,
            'pagination' => $pagination,
            'filters'    => ['is_approved' => $isApproved],
        ], 'admin');
    }

    /**
     * POST /admin/yorumlar/{id}/onayla — approve.
     */
    public function approve(int $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        (new CommentModel())->approve($id);
        Session::setFlash('success', __('admin_comment_approved'));
        View::redirect($this->backTo());
    }

    /**
     * POST /admin/yorumlar/{id}/reddet — reject (deletes, per schema).
     */
    public function reject(int $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        (new CommentModel())->reject($id);
        Session::setFlash('success', __('admin_comment_rejected'));
        View::redirect($this->backTo());
    }

    /**
     * POST /admin/yorumlar/{id}/sil — delete.
     */
    public function delete(int $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        (new CommentModel())->reject($id);
        Session::setFlash('success', __('admin_comment_deleted'));
        View::redirect($this->backTo());
    }

    private function baseUrl(): string
    {
        $query = $_GET;
        unset($query['page']);
        $qs = http_build_query($query);

        return '/admin/yorumlar' . ($qs !== '' ? '?' . $qs : '');
    }

    private function backTo(): string
    {
        $ref = (string) ($_POST['redirect'] ?? '');
        return $ref !== '' && str_starts_with($ref, '/admin') ? $ref : '/admin/yorumlar';
    }
}
