<?php
/**
 * Author dashboard: the current author's own articles + quick stats.
 * Minimal correct version created during Integration/QA (original was missing).
 *
 * @var array                 $articles
 * @var array                 $stats       {article_count,total_views}
 * @var \App\Core\Pagination  $pagination
 */

use App\Core\Session;

$token = Session::getToken();
?>
<h1><?= e(__('dashboard_title')) ?></h1>

<div class="stat-grid">
  <div class="stat-card">
    <div class="num"><?= number_format((int) ($stats['article_count'] ?? 0)) ?></div>
    <div class="label"><?= e(__('label_article_count', ['count' => (int) ($stats['article_count'] ?? 0)])) ?></div>
  </div>
  <div class="stat-card">
    <div class="num"><?= number_format((int) ($stats['total_views'] ?? 0)) ?></div>
    <div class="label"><?= e(__('label_views')) ?></div>
  </div>
</div>

<p><a class="btn btn-primary" href="/yazar-paneli/yeni"><?= e(__('editor_new_title')) ?></a></p>

<div class="table-wrap">
  <table class="data">
    <thead><tr>
      <th><?= e(__('admin_col_title')) ?></th>
      <th><?= e(__('admin_col_status')) ?></th>
      <th><?= e(__('admin_col_views')) ?></th>
      <th><?= e(__('admin_col_actions')) ?></th>
    </tr></thead>
    <tbody>
    <?php if ($articles === []): ?>
      <tr><td colspan="4" class="empty"><?= e(__('no_results')) ?></td></tr>
    <?php else: foreach ($articles as $a): $aid = (int) $a['id']; $status = (string) ($a['status'] ?? 'draft'); ?>
      <tr>
        <td><a href="/yazar-paneli/duzenle/<?= $aid ?>"><?= e($a['title'] ?? $a['slug'] ?? ('#' . $aid)) ?></a></td>
        <td><span class="badge-status <?= e($status) ?>"><?= e(__('status_' . $status)) ?></span></td>
        <td><?= number_format((int) ($a['view_count'] ?? 0)) ?></td>
        <td class="actions">
          <a class="btn btn-sm" href="/yazar-paneli/duzenle/<?= $aid ?>"><?= e(__('admin_action_edit')) ?></a>
          <?php if (in_array($status, ['draft', 'rejected'], true)): ?>
            <form method="post" action="/yazar-paneli/gonder/<?= $aid ?>">
              <input type="hidden" name="_csrf" value="<?= e($token) ?>">
              <button class="btn btn-sm btn-success" type="submit"><?= e(__('admin_action_publish')) ?></button>
            </form>
          <?php endif; ?>
          <?php if ($status === 'draft'): ?>
            <form method="post" action="/yazar-paneli/sil/<?= $aid ?>" onsubmit="return confirm('<?= e(__('admin_confirm_delete')) ?>');">
              <input type="hidden" name="_csrf" value="<?= e($token) ?>">
              <button class="btn btn-sm btn-danger" type="submit"><?= e(__('admin_action_delete')) ?></button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?= \App\Core\View::renderPartial('admin/_pagination', ['pagination' => $pagination]) ?>
