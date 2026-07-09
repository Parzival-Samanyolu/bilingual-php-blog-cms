<?php
/**
 * Category listing page.
 *
 * @var string                          $lang
 * @var array<string,mixed>             $category
 * @var array<int,array<string,mixed>>  $articles
 * @var array<int,array<string,mixed>>  $children
 * @var array<int,array<string,mixed>>  $breadcrumb
 * @var int                             $total
 * @var mixed                           $pagination
 */

use App\Core\View;

$catBase = $lang === 'en' ? '/category/' : '/kategori/';
$name = (string) ($category['name'] ?? '');
$description = (string) ($category['description'] ?? '');
?>
<div class="container">
    <nav class="breadcrumb" aria-label="breadcrumb">
        <a href="/"><?= e(__('nav_home')) ?></a>
        <?php foreach ($breadcrumb as $crumb): ?>
            <span class="breadcrumb__sep">/</span>
            <a href="<?= e($catBase . rawurlencode((string) ($crumb['slug'] ?? ''))) ?>">
                <?= e((string) ($crumb['name'] ?? '')) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <header class="category-header">
        <h1 class="category-header__title"><?= e($name) ?></h1>
        <?php if ($description !== ''): ?>
            <p class="category-header__desc"><?= e($description) ?></p>
        <?php endif; ?>
        <p class="category-header__count"><?= e(__('label_article_count', ['count' => $total])) ?></p>
    </header>

    <div class="category-layout">
        <?php if (!empty($children)): ?>
            <aside class="category-sidebar">
                <h2 class="category-sidebar__heading"><?= e(__('subcategories')) ?></h2>
                <ul class="category-sidebar__list">
                    <?php foreach ($children as $child): ?>
                        <li>
                            <a href="<?= e($catBase . rawurlencode((string) ($child['slug'] ?? ''))) ?>">
                                <?= e((string) ($child['name'] ?? '')) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </aside>
        <?php endif; ?>

        <div class="category-main">
            <?php if (empty($articles)): ?>
                <p class="empty-state"><?= e(__('no_results')) ?></p>
            <?php else: ?>
                <div class="article-grid grid-3">
                    <?php foreach ($articles as $article): ?>
                        <?= View::renderPartial('partials/article-card', ['article' => $article]) ?>
                    <?php endforeach; ?>
                </div>
                <?= View::renderPartial('partials/pagination', ['pagination' => $pagination]) ?>
            <?php endif; ?>
        </div>
    </div>
</div>
