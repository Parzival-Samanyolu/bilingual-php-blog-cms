<?php
/**
 * Admin article listing with filters.
 *
 * @var array                 $articles
 * @var \App\Core\Pagination  $pagination
 * @var array                 $categories  category tree
 * @var array                 $authors
 * @var array                 $filters
 */

$flattenCats = static function (array $nodes, int $depth, callable $self): array {
    $out = [];
    foreach ($nodes as $node) {
        $out[] = ['id' => (int) $node['id'], 'name' => str_repeat('— ', $depth) . (string) $node['name']];
        if (!empty($node['children'])) {
            $out = array_merge($out, $self($node['children'], $depth + 1, $self));
        }
    }
    return $out;
};
$catOptions = $flattenCats($categories, 0, $flattenCats);
$statuses = ['draft', 'pending', 'published', 'rejected'];
?>
<h1><?= e(__('admin_articles')) ?></h1>

<form class="filters" method="get" action="/admin/yazilar">
  <div class="field">
    <label for="f-status"><?= e(__('admin_col_status')) ?></label>
    <select id="f-status" name="status">
      <option value=""><?= e(__('admin_filter_all')) ?></option>
      <?php foreach ($statuses as $s): ?>
        <option value="<?= e($s) ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= e(__('status_' . $s)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label for="f-cat"><?= e(__('admin_col_category')) ?></label>
    <select id="f-cat" name="category">
      <option value=""><?= e(__('admin_filter_all')) ?></option>
      <?php foreach ($catOptions as $opt): ?>
        <option value="<?= (int) $opt['id'] ?>" <?= (int) ($filters['category'] ?? 0) === $opt['id'] ? 'selected' : '' ?>><?= e($opt['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label for="f-author"><?= e(__('admin_col_author')) ?></label>
    <select id="f-author" name="author">
      <option value=""><?= e(__('admin_filter_all')) ?></option>
      <?php foreach ($authors as $au): ?>
        <option value="<?= (int) $au['id'] ?>" <?= (int) ($filters['author'] ?? 0) === (int) $au['id'] ? 'selected' : '' ?>><?= e($au['name']) ?> (@<?= e($au['username']) ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label for="f-lang"><?= e(__('admin_col_lang')) ?></label>
    <select id="f-lang" name="lang">
      <option value=""><?= e(__('admin_filter_all')) ?></option>
      <option value="tr" <?= ($filters['lang'] ?? '') === 'tr' ? 'selected' : '' ?>>TR</option>
      <option value="en" <?= ($filters['lang'] ?? '') === 'en' ? 'selected' : '' ?>>EN</option>
    </select>
  </div>
  <div class="field">
    <button class="btn btn-primary" type="submit"><?= e(__('admin_filter_apply')) ?></button>
  </div>
</form>

<div class="table-wrap">
  <table class="data">
    <thead><tr>
      <th><?= e(__('admin_col_title')) ?></th>
      <th><?= e(__('admin_col_category')) ?></th>
      <th><?= e(__('admin_col_author')) ?></th>
      <th><?= e(__('admin_col_lang')) ?></th>
      <th><?= e(__('admin_col_status')) ?></th>
      <th><?= e(__('admin_col_views')) ?></th>
      <th><?= e(__('admin_col_actions')) ?></th>
    </tr></thead>
    <tbody>
    <?php if ($articles === []): ?>
      <tr><td colspan="7" class="empty"><?= e(__('admin_no_articles')) ?></td></tr>
    <?php else: foreach ($articles as $a): $aid = (int) $a['id']; ?>
      <tr>
        <td><a href="/admin/yazilar/<?= $aid ?>/duzenle"><?= e($a['title'] ?? $a['slug']) ?></a></td>
        <td class="muted"><?= e($a['category_name'] ?? '') ?></td>
        <td class="muted"><?= e($a['author_name'] ?? '') ?></td>
        <td><?= e(strtoupper((string) $a['lang'])) ?></td>
        <td><span class="badge-status <?= e($a['status']) ?>"><?= e(__('status_' . $a['status'])) ?></span></td>
        <td><?= number_format((int) $a['view_count']) ?></td>
        <td class="actions">
          <a class="btn btn-sm" href="/admin/yazilar/<?= $aid ?>/duzenle"><?= e(__('admin_action_edit')) ?></a>
          <?php if ($a['status'] !== 'published'): ?>
            <form method="post" action="/admin/yazilar/<?= $aid ?>/onayla">
              <input type="hidden" name="_csrf" value="<?= e(\App\Core\Session::getToken()) ?>">
              <button class="btn btn-sm btn-success" type="submit"><?= e(__('admin_action_publish')) ?></button>
            </form>
          <?php endif; ?>
          <?php if ($a['status'] !== 'rejected'): ?>
            <form method="post" action="/admin/yazilar/<?= $aid ?>/reddet">
              <input type="hidden" name="_csrf" value="<?= e(\App\Core\Session::getToken()) ?>">
              <button class="btn btn-sm" type="submit"><?= e(__('admin_action_reject')) ?></button>
            </form>
          <?php endif; ?>
          <form method="post" action="/admin/yazilar/<?= $aid ?>/sil" onsubmit="return confirm('<?= e(__('admin_confirm_delete')) ?>');">
            <input type="hidden" name="_csrf" value="<?= e(\App\Core\Session::getToken()) ?>">
            <button class="btn btn-sm btn-danger" type="submit"><?= e(__('admin_action_delete')) ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?= \App\Core\View::renderPartial('admin/_pagination', ['pagination' => $pagination]) ?>
