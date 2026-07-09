<?php
/**
 * Admin dashboard.
 *
 * @var int   $publishedCount
 * @var int   $pendingArticleCount
 * @var int   $pendingCommentCount
 * @var int   $totalUsers
 * @var int   $totalViews
 * @var int   $draftCount
 * @var array $recentPending
 * @var array $recentComments
 */
?>
<h1><?= e(__('admin_dashboard')) ?></h1>

<div class="stat-grid">
  <div class="stat-card">
    <div class="ico">&#128196;</div>
    <div class="num"><?= number_format((int) $publishedCount) ?></div>
    <div class="label"><?= e(__('admin_stat_published')) ?></div>
  </div>
  <div class="stat-card">
    <div class="ico">&#9203;</div>
    <div class="num"><?= number_format((int) $pendingArticleCount) ?></div>
    <div class="label"><?= e(__('admin_stat_pending_articles')) ?></div>
  </div>
  <div class="stat-card">
    <div class="ico">&#128172;</div>
    <div class="num"><?= number_format((int) $pendingCommentCount) ?></div>
    <div class="label"><?= e(__('admin_stat_pending_comments')) ?></div>
  </div>
  <div class="stat-card">
    <div class="ico">&#128100;</div>
    <div class="num"><?= number_format((int) $totalUsers) ?></div>
    <div class="label"><?= e(__('admin_stat_users')) ?></div>
  </div>
  <div class="stat-card">
    <div class="ico">&#128065;</div>
    <div class="num"><?= number_format((int) $totalViews) ?></div>
    <div class="label"><?= e(__('admin_stat_views')) ?></div>
  </div>
  <div class="stat-card">
    <div class="ico">&#128221;</div>
    <div class="num"><?= number_format((int) $draftCount) ?></div>
    <div class="label"><?= e(__('admin_stat_drafts')) ?></div>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <h2><?= e(__('admin_recent_pending_articles')) ?></h2>
    <?php if ($recentPending === []): ?>
      <p class="muted"><?= e(__('admin_no_pending_articles')) ?></p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="data">
          <thead><tr>
            <th><?= e(__('admin_col_title')) ?></th>
            <th><?= e(__('admin_col_author')) ?></th>
            <th><?= e(__('admin_col_actions')) ?></th>
          </tr></thead>
          <tbody>
          <?php foreach ($recentPending as $a): ?>
            <tr>
              <td><?= e($a['title'] ?? $a['slug']) ?></td>
              <td class="muted"><?= e($a['author_name'] ?? '') ?></td>
              <td class="actions">
                <a class="btn btn-sm" href="/admin/yazilar/<?= (int) $a['id'] ?>/duzenle"><?= e(__('admin_action_review')) ?></a>
                <form method="post" action="/admin/yazilar/<?= (int) $a['id'] ?>/onayla">
                  <input type="hidden" name="_csrf" value="<?= e(\App\Core\Session::getToken()) ?>">
                  <button class="btn btn-sm btn-success" type="submit"><?= e(__('admin_action_approve')) ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2><?= e(__('admin_recent_pending_comments')) ?></h2>
    <?php if ($recentComments === []): ?>
      <p class="muted"><?= e(__('admin_no_pending_comments')) ?></p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="data">
          <thead><tr>
            <th><?= e(__('admin_col_comment')) ?></th>
            <th><?= e(__('admin_col_user')) ?></th>
            <th><?= e(__('admin_col_actions')) ?></th>
          </tr></thead>
          <tbody>
          <?php foreach ($recentComments as $c): ?>
            <tr>
              <td><?= e(mb_strimwidth((string) $c['content'], 0, 70, '…', 'UTF-8')) ?></td>
              <td class="muted"><?= e($c['user_name'] ?? '') ?></td>
              <td class="actions">
                <form method="post" action="/admin/yorumlar/<?= (int) $c['id'] ?>/onayla">
                  <input type="hidden" name="_csrf" value="<?= e(\App\Core\Session::getToken()) ?>">
                  <button class="btn btn-sm btn-success" type="submit"><?= e(__('admin_action_approve')) ?></button>
                </form>
                <form method="post" action="/admin/yorumlar/<?= (int) $c['id'] ?>/reddet">
                  <input type="hidden" name="_csrf" value="<?= e(\App\Core\Session::getToken()) ?>">
                  <button class="btn btn-sm btn-danger" type="submit"><?= e(__('admin_action_reject')) ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
