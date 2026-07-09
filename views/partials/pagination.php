<?php
/**
 * Pagination link bar.
 *
 * Expected variable:
 *   $pagination — an App\Core\Pagination instance OR its getLinks() array.
 *
 * @var mixed $pagination
 */

if (!isset($pagination)) {
    return;
}

if (is_object($pagination) && method_exists($pagination, 'getLinks')) {
    if (method_exists($pagination, 'hasPages') && !$pagination->hasPages()) {
        return;
    }
    $links = $pagination->getLinks();
} elseif (is_array($pagination)) {
    $links = $pagination;
} else {
    return;
}

if (!is_array($links) || $links === []) {
    return;
}
?>
<nav class="pagination" aria-label="<?= e(__('pagination_label')) ?>">
    <ul class="pagination__list">
        <?php foreach ($links as $link): ?>
            <?php
            $label    = (string) ($link['label'] ?? '');
            $url      = $link['url'] ?? null;
            $active   = !empty($link['active']);
            $disabled = !empty($link['disabled']);
            $classes  = 'pagination__item'
                . ($active ? ' pagination__item--active' : '')
                . ($disabled ? ' pagination__item--disabled' : '');
            ?>
            <li class="<?= e($classes) ?>">
                <?php if ($url !== null && !$active && !$disabled): ?>
                    <a class="pagination__link" href="<?= e((string) $url) ?>"><?= e($label) ?></a>
                <?php else: ?>
                    <span class="pagination__link" aria-current="<?= $active ? 'page' : 'false' ?>"><?= e($label) ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
