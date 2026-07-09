<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Pagination;
use App\Core\Session;
use App\Core\View;
use App\Models\UserModel;

/**
 * Admin management of user accounts: approval, roles, banning, deletion.
 */
class AdminUserController extends BaseController
{
    private const PER_PAGE = 25;
    private const VALID_ROLES = ['admin', 'author', 'reader'];

    /**
     * GET /admin/kullanicilar — filterable user list.
     */
    public function list(): void
    {
        $this->requireAdmin();

        $db = Database::getInstance();

        $role       = (string) ($_GET['role'] ?? '');
        $isApproved = ($_GET['is_approved'] ?? '') !== '' ? (int) $_GET['is_approved'] : null;
        $page       = max(1, (int) ($_GET['page'] ?? 1));

        $where  = [];
        $params = [];

        if (in_array($role, self::VALID_ROLES, true)) {
            $where[]  = '`role` = ?';
            $params[] = $role;
        }
        if ($isApproved !== null && in_array($isApproved, [-1, 0, 1], true)) {
            $where[]  = '`is_approved` = ?';
            $params[] = $isApproved;
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int) $db->fetchColumn("SELECT COUNT(*) FROM `users` {$whereSql}", $params);

        $pagination = new Pagination($total, self::PER_PAGE, $page, $this->baseUrl());
        $offset = $pagination->getOffset();

        $users = $db->fetchAll(
            "SELECT `id`, `name`, `username`, `email`, `role`, `is_approved`, `avatar`, `created_at`
             FROM `users`
             {$whereSql}
             ORDER BY `created_at` DESC
             LIMIT " . self::PER_PAGE . " OFFSET " . (int) $offset,
            $params
        );

        View::render('admin/users/list', [
            'pageTitle'  => __('admin_users'),
            'users'      => $users,
            'pagination' => $pagination,
            'filters'    => ['role' => $role, 'is_approved' => $isApproved],
            'currentUserId' => (int) (Session::getUser()['id'] ?? 0),
        ], 'admin');
    }

    /**
     * POST /admin/kullanicilar/{id}/onayla — approve a pending account.
     */
    public function approveUser(int $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        (new UserModel())->approveUser($id);
        Session::setFlash('success', __('admin_user_approved'));
        View::redirect($this->backTo());
    }

    /**
     * POST /admin/kullanicilar/{id}/rol — change a user's role.
     */
    public function setRole(int $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $role = (string) ($_POST['role'] ?? '');
        if (!in_array($role, self::VALID_ROLES, true)) {
            Session::setFlash('error', __('admin_invalid_role'));
            View::redirect($this->backTo());
            return;
        }

        (new UserModel())->setRole($id, $role);
        Session::setFlash('success', __('admin_user_role_updated'));
        View::redirect($this->backTo());
    }

    /**
     * POST /admin/kullanicilar/{id}/ban — ban (is_approved = -1).
     */
    public function banUser(int $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        if ($id === (int) (Session::getUser()['id'] ?? 0)) {
            Session::setFlash('error', __('admin_cannot_ban_self'));
            View::redirect($this->backTo());
            return;
        }

        (new UserModel())->banUser($id);
        Session::setFlash('success', __('admin_user_banned'));
        View::redirect($this->backTo());
    }

    /**
     * POST /admin/kullanicilar/{id}/sil — delete a user account.
     */
    public function delete(int $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        if ($id === (int) (Session::getUser()['id'] ?? 0)) {
            Session::setFlash('error', __('admin_cannot_delete_self'));
            View::redirect($this->backTo());
            return;
        }

        (new UserModel())->delete($id);
        Session::setFlash('success', __('admin_user_deleted'));
        View::redirect($this->backTo());
    }

    private function baseUrl(): string
    {
        $query = $_GET;
        unset($query['page']);
        $qs = http_build_query($query);

        return '/admin/kullanicilar' . ($qs !== '' ? '?' . $qs : '');
    }

    private function backTo(): string
    {
        $ref = (string) ($_POST['redirect'] ?? '');
        return $ref !== '' && str_starts_with($ref, '/admin') ? $ref : '/admin/kullanicilar';
    }
}
