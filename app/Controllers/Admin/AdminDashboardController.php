<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\View;
use App\Models\ArticleModel;
use App\Models\CommentModel;

/**
 * Admin dashboard: high-level stats and quick moderation queues.
 */
class AdminDashboardController extends BaseController
{
    /**
     * GET /admin — overview dashboard.
     */
    public function index(): void
    {
        $this->requireAdmin();

        $db = Database::getInstance();
        $articleModel = new ArticleModel();
        $commentModel = new CommentModel();

        $publishedCount = (int) $db->fetchColumn(
            "SELECT COUNT(*) FROM `articles` WHERE `status` = 'published'"
        );
        $pendingArticleCount = (int) $db->fetchColumn(
            "SELECT COUNT(*) FROM `articles` WHERE `status` = 'pending'"
        );
        $pendingCommentCount = $commentModel->countPending();
        $totalUsers = (int) $db->fetchColumn("SELECT COUNT(*) FROM `users`");
        $totalViews = (int) $db->fetchColumn(
            "SELECT COALESCE(SUM(`view_count`), 0) FROM `articles`"
        );
        $draftCount = (int) $db->fetchColumn(
            "SELECT COUNT(*) FROM `articles` WHERE `status` = 'draft'"
        );

        $recentPending = array_slice($articleModel->getPending(), 0, 5);
        $recentComments = $commentModel->getPending(5, 0);

        View::render('admin/dashboard', [
            'pageTitle'           => __('admin_dashboard'),
            'publishedCount'      => $publishedCount,
            'pendingArticleCount' => $pendingArticleCount,
            'pendingCommentCount' => $pendingCommentCount,
            'totalUsers'          => $totalUsers,
            'totalViews'          => $totalViews,
            'draftCount'          => $draftCount,
            'recentPending'       => $recentPending,
            'recentComments'      => $recentComments,
        ], 'admin');
    }
}
