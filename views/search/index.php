<?php
/**
 * Search results page.
 *
 * @var string                          $lang
 * @var string                          $query
 * @var string                          $categorySlug
 * @var string                          $tagSlug
 * @var array<int,array<string,mixed>>  $results
 * @var int                             $total
 * @var mixed                           $pagination
 * @var array<int,array{id:int,name:string,slug:string,depth:int}> $categoryOptions
 * @var array<int,array<string,mixed>>  $tagOptions
 */

use App\Core\View;

$action = $lang === 'en' ? '/search' : '/ara';
?>
<div class="container search-page">
    <h1 class="search-page__title"><?= e(__('nav_search')) ?></h1>

    <form class="search-filters" action="<?= e($action) ?>" method="get" role="search">
        <div class="search-filters__row">
            <input type="search" class="search-filters__q" name="q"
                   value="<?= e($query) ?>"
                   placeholder="<?= e(__('search_placeholder')) ?>"
                   aria-label="<?= e(__('nav_search')) ?>">
            <button type="submit" class="btn btn--primary"><?= e(__('nav_search')) ?></button>
        </div>
        <div class="search-filters__row search-filters__row--filters">
            <label class="search-filters__field">
                <span class="search-filters__label"><?= e(__('filter_category')) ?></span>
                <select name="category" class="search-filters__select">
                    <option value=""><?= e(__('filter_all')) ?></option>
                    <?php foreach ($categoryOptions as $opt): ?>
                        <?php $prefix = str_repeat('— ', (int) ($opt['depth'] ?? 0)); ?>
                        <option value="<?= e((string) ($opt['slug'] ?? '')) ?>"
                            <?= ((string) ($opt['slug'] ?? '') === $categorySlug) ? 'selected' : '' ?>>
                            <?= e($prefix . (string) ($opt['name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="search-filters__field">
                <span class="search-filters__label"><?= e(__('filter_tag')) ?></span>
                <select name="tag" class="search-filters__select">
                    <option value=""><?= e(__('filter_all')) ?></option>
                    <?php foreach ($tagOptions as $opt): ?>
                        <option value="<?= e((string) ($opt['slug'] ?? '')) ?>"
                            <?= ((string) ($opt['slug'] ?? '') === $tagSlug) ? 'selected' : '' ?>>
                            <?= e((string) ($opt['name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
    </form>

    <?php if ($query !== ''): ?>
        <p class="search-page__summary">
            <?= e(__('search_results_for', ['query' => $query])) ?>
            <span class="search-page__count"><?= e(__('search_result_count', ['count' => $total])) ?></span>
        </p>
    <?php endif; ?>

    <?php if ($query === ''): ?>
        <p class="empty-state"><?= e(__('search_prompt')) ?></p>
    <?php elseif (empty($results)): ?>
        <p class="empty-state"><?= e(__('no_results')) ?></p>
    <?php else: ?>
        <div class="article-grid grid-3">
            <?php foreach ($results as $article): ?>
                <?= View::renderPartial('partials/article-card', ['article' => $article]) ?>
            <?php endforeach; ?>
        </div>
        <?= View::renderPartial('partials/pagination', ['pagination' => $pagination]) ?>
    <?php endif; ?>
</div>
