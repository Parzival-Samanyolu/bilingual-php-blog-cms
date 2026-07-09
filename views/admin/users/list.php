<?php
/**
 * Admin user management. Rendered in the admin layout.
 * Minimal correct version created during Integration/QA (original was missing).
 *
 * @var array                 $users
 * @var \App\Core\Pagination  $pagination
 * @var array                 $filters        {role,is_approved}
 * @var int                   $currentUserId
 */

use App\Core\Session;

$token = Session::getToken();
$roles = ['admin', 'author', 'reader'];
$apprLabel = static function (int $v): array {
    return match ($v) {
        1 => ['yes', 'onaylı'],
        -1 => ['ban', 'yasaklı'],
        default => ['no', 'beklemede'],
    };
};
?>
<h1><?= e(__('admin_users')) ?></h1>

<form class="filters" method="get" action="/admin/kullanicilar">
  <div class="field">
    <label for="f-role"><?= e(__('admin_col_role') ?: 'Role') ?></label>
    <select id="f-role" name="role">
      <option value=""><?= e(__('admin_filter_all')) ?></option>
      <?php foreach ($roles as $r): ?>
        <option value="<?= e($r) ?>" <?= ($filters['role'] ?? '') === $r ? 'selected' : '' ?>><?= e($r) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label for="f-appr">Durum</label>
    <select id="f-appr" name="is_approved">
      <option value=""><?= e(__('admin_filter_all')) ?></option>
      <option value="1" <?= ($filters['is_approved'] ?? null) === 1 ? 'selected' : '' ?>>Onaylı</option>
      <option value="0" <?= ($filters['is_approved'] ?? null) === 0 ? 'selected' : '' ?>>Beklemede</option>
      <option value="-1" <?= ($filters['is_approved'] ?? null) === -1 ? 'selected' : '' ?>>Yasaklı</option>
    </select>
  </div>
  <div class="field">
    <button class="btn btn-primary" type="submit"><?= e(__('admin_filter_apply')) ?></button>
  </div>
</form>

<div class="table-wrap">
  <table class="data">
    <thead><tr>
      <th>Ad</th><th>Kullanıcı</th><th>E-posta</th><th>Rol</th><th>Durum</th><th><?= e(__('admin_col_actions')) ?></th>
    </tr></thead>
    <tbody>
    <?php if ($users === []): ?>
      <tr><td colspan="6" class="empty"><?= e(__('no_results')) ?></td></tr>
    <?php else: foreach ($users as $u): $uid = (int) $u['id']; [$ac, $al] = $apprLabel((int) $u['is_approved']); ?>
      <tr>
        <td><?= e((string) ($u['name'] ?? '')) ?></td>
        <td class="muted">@<?= e((string) ($u['username'] ?? '')) ?></td>
        <td class="muted"><?= e((string) ($u['email'] ?? '')) ?></td>
        <td><span class="badge-role <?= e((string) $u['role']) ?>"><?= e((string) $u['role']) ?></span></td>
        <td><span class="badge-appr <?= e($ac) ?>"><?= e($al) ?></span></td>
        <td class="actions">
          <?php if ((int) $u['is_approved'] === 0): ?>
            <form method="post" action="/admin/kullanicilar/<?= $uid ?>/onayla">
              <input type="hidden" name="_csrf" value="<?= e($token) ?>">
              <button class="btn btn-sm btn-success" type="submit"><?= e(__('admin_action_approve') ?: 'Onayla') ?></button>
            </form>
          <?php endif; ?>
          <form method="post" action="/admin/kullanicilar/<?= $uid ?>/rol" class="field-inline">
            <input type="hidden" name="_csrf" value="<?= e($token) ?>">
            <select name="role">
              <?php foreach ($roles as $r): ?>
                <option value="<?= e($r) ?>" <?= (string) $u['role'] === $r ? 'selected' : '' ?>><?= e($r) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-sm" type="submit">Rol</button>
          </form>
          <?php if ($uid !== $currentUserId): ?>
            <?php if ((int) $u['is_approved'] !== -1): ?>
              <form method="post" action="/admin/kullanicilar/<?= $uid ?>/ban">
                <input type="hidden" name="_csrf" value="<?= e($token) ?>">
                <button class="btn btn-sm" type="submit">Yasakla</button>
              </form>
            <?php endif; ?>
            <form method="post" action="/admin/kullanicilar/<?= $uid ?>/sil" onsubmit="return confirm('<?= e(__('admin_confirm_delete')) ?>');">
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
