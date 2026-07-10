<?php
/**
 * Reusable admin pagination bar.
 *
 * @var \App\Core\Pagination $pagination
 */
if (!isset($pagination) || !$pagination->hasPages()) {
    return;
}
?>
<nav class="admin-pagination">
  <?php foreach ($pagination->getLinks() as $link): ?>
    <?php if (!empty($link['url']) && empty($link['active'])): ?>
      <a class="btn btn-sm" href="<?= e($link['url']) ?>"><?= e($link['label']) ?></a>
    <?php elseif (!empty($link['active'])): ?>
      <span class="btn btn-sm btn-primary"><?= e($link['label']) ?></span>
    <?php else: ?>
      <span class="btn btn-sm is-disabled"><?= e($link['label']) ?></span>
    <?php endif; ?>
  <?php endforeach; ?>
</nav>
