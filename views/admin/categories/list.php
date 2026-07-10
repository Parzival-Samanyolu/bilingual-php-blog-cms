<?php
/**
 * Admin category tree table.
 *
 * @var array $tree  nested category nodes
 */

$renderRows = static function (array $nodes, int $depth, callable $self): string {
    $html = '';
    foreach ($nodes as $node) {
        $id = (int) $node['id'];
        $pad = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth);
        $arrow = $depth > 0 ? '&#8627; ' : '';
        $html .= '<tr>'
            . '<td><span class="tree-indent">' . $pad . $arrow . '</span>' . e((string) $node['name']) . '</td>'
            . '<td class="muted">' . e((string) $node['slug']) . '</td>'
            . '<td>' . (int) $node['sort_order'] . '</td>'
            . '<td class="actions">'
            . '<a class="btn btn-sm" href="/admin/kategoriler/' . $id . '">' . e(__('admin_action_edit')) . '</a>'
            . '<form method="post" action="/admin/kategoriler/' . $id . '/sil" onsubmit="return confirm(\'' . e(__('admin_confirm_delete')) . '\');">'
            . '<input type="hidden" name="_csrf" value="' . e(\App\Core\Session::getToken()) . '">'
            . '<button class="btn btn-sm btn-danger" type="submit">' . e(__('admin_action_delete')) . '</button>'
            . '</form>'
            . '</td></tr>';
        if (!empty($node['children'])) {
            $html .= $self($node['children'], $depth + 1, $self);
        }
    }
    return $html;
};
?>
<h1><?= e(__('admin_categories')) ?></h1>

<div class="admin-mb">
  <a class="btn btn-primary" href="/admin/kategoriler/yeni"><?= e(__('admin_category_new')) ?></a>
</div>

<div class="table-wrap">
  <table class="data">
    <thead><tr>
      <th><?= e(__('admin_col_name')) ?></th>
      <th><?= e(__('admin_field_slug')) ?></th>
      <th><?= e(__('admin_col_sort')) ?></th>
      <th><?= e(__('admin_col_actions')) ?></th>
    </tr></thead>
    <tbody>
    <?php if ($tree === []): ?>
      <tr><td colspan="4" class="empty"><?= e(__('admin_no_categories')) ?></td></tr>
    <?php else: ?>
      <?= $renderRows($tree, 0, $renderRows) ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
