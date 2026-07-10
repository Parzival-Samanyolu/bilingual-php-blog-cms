<?php
/**
 * Admin comment moderation. Rendered in the admin layout.
 * Minimal correct version created during Integration/QA (original was missing).
 *
 * @var array                 $comments
 * @var \App\Core\Pagination  $pagination
 * @var array                 $filters    {is_approved}
 */

use App\Core\Session;

$token = Session::getToken();
?>
<h1><?= e(__('admin_comments')) ?></h1>

<form class="filters" method="get" action="/admin/yorumlar">
  <div class="field">
    <label for="f-appr"><?= e(__('admin_col_status')) ?></label>
    <select id="f-appr" name="is_approved">
      <option value=""><?= e(__('admin_filter_all')) ?></option>
      <option value="0" <?= ($filters['is_approved'] ?? null) === 0 ? 'selected' : '' ?>><?= e(__('label_pending')) ?></option>
      <option value="1" <?= ($filters['is_approved'] ?? null) === 1 ? 'selected' : '' ?>><?= e(__('label_approved')) ?></option>
    </select>
  </div>
  <div class="field">
    <button class="btn btn-primary" type="submit"><?= e(__('admin_filter_apply')) ?></button>
  </div>
</form>

<div class="table-wrap">
  <table class="data">
    <thead><tr>
      <th><?= e(__('admin_col_comment')) ?></th><th><?= e(__('admin_col_user')) ?></th><th><?= e(__('admin_col_article')) ?></th><th><?= e(__('admin_col_status')) ?></th><th><?= e(__('admin_col_actions')) ?></th>
    </tr></thead>
    <tbody>
    <?php if ($comments === []): ?>
      <tr><td colspan="5" class="empty"><?= e(__('no_comments_yet')) ?></td></tr>
    <?php else: foreach ($comments as $c): $cid = (int) $c['id']; $approved = (int) $c['is_approved'] === 1; ?>
      <tr>
        <td><?= e(mb_substr((string) $c['content'], 0, 160)) ?></td>
        <td class="muted"><?= e((string) ($c['user_name'] ?? '')) ?> <span class="muted">@<?= e((string) ($c['user_username'] ?? '')) ?></span></td>
        <td class="muted"><a href="/yazi/<?= e(rawurlencode((string) ($c['article_category_slug'] ?? 'genel'))) ?>/<?= e(rawurlencode((string) ($c['article_slug'] ?? ''))) ?>"><?= e((string) ($c['article_title'] ?? $c['article_slug'] ?? '')) ?></a></td>
        <td><span class="badge-appr <?= $approved ? 'yes' : 'no' ?>"><?= e($approved ? __('label_approved') : __('label_pending')) ?></span></td>
        <td class="actions">
          <?php if (!$approved): ?>
            <form method="post" action="/admin/yorumlar/<?= $cid ?>/onayla">
              <input type="hidden" name="_csrf" value="<?= e($token) ?>">
              <button class="btn btn-sm btn-success" type="submit"><?= e(__('admin_action_approve')) ?></button>
            </form>
          <?php endif; ?>
          <form method="post" action="/admin/yorumlar/<?= $cid ?>/reddet" onsubmit="return confirm('<?= e(__('admin_confirm_delete')) ?>');">
            <input type="hidden" name="_csrf" value="<?= e($token) ?>">
            <button class="btn btn-sm btn-danger" type="submit"><?= e(__('admin_action_reject')) ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?= \App\Core\View::renderPartial('admin/_pagination', ['pagination' => $pagination]) ?>
