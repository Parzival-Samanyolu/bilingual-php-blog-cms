<?php
/**
 * Home page.
 *
 * @var string                          $lang
 * @var array<int,array<string,mixed>>  $trending
 * @var array<int,array<string,mixed>>  $latest
 * @var array<int,array<string,mixed>>  $categoryTree
 * @var array<int,array<string,mixed>>  $popularTags
 */

use App\Core\View;

$searchAction = $lang === 'en' ? '/search' : '/ara';
$catBase = $lang === 'en' ? '/category/' : '/kategori/';
$searchBase = $lang === 'en' ? '/search' : '/ara';

$card = static function (array $article): void {
    echo View::renderPartial('partials/article-card', ['article' => $article]);
};
?>
<section class="hero">
    <div class="container hero__inner">
        <h1 class="hero__title"><?= e(__('hero_heading')) ?></h1>
        <p class="hero__subtitle"><?= e(__('hero_subtitle')) ?></p>
        <form class="hero__search" action="<?= e($searchAction) ?>" method="get" role="search">
            <input type="search" class="hero__search-input" name="q"
                   placeholder="<?= e(__('search_placeholder')) ?>"
                   aria-label="<?= e(__('nav_search')) ?>">
            <button type="submit" class="hero__search-btn btn btn--primary"><?= e(__('nav_search')) ?></button>
        </form>
    </div>
</section>

<?php if (!empty($trending)): ?>
    <section class="section trending">
        <div class="container">
            <h2 class="section__title"><?= e(__('section_trending')) ?></h2>
            <div class="trending__strip">
                <?php foreach ($trending as $article): ?>
                    <?php $card($article); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if (!empty($categoryTree)): ?>
    <section class="section categories">
        <div class="container">
            <h2 class="section__title"><?= e(__('section_categories')) ?></h2>
            <div class="category-cards">
                <?php foreach ($categoryTree as $root): ?>
                    <a class="category-card" href="<?= e($catBase . rawurlencode((string) ($root['slug'] ?? ''))) ?>">
                        <span class="category-card__name"><?= e((string) ($root['name'] ?? '')) ?></span>
                        <?php if (!empty($root['children']) && is_array($root['children'])): ?>
                            <span class="category-card__sub">
                                <?php
                                $subNames = array_map(
                                    static fn (array $c): string => (string) ($c['name'] ?? ''),
                                    array_slice($root['children'], 0, 3)
                                );
                                echo e(implode(' · ', array_filter($subNames)));
                                ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="section latest">
    <div class="container">
        <h2 class="section__title"><?= e(__('section_latest')) ?></h2>
        <?php if (empty($latest)): ?>
            <p class="empty-state"><?= e(__('no_results')) ?></p>
        <?php else: ?>
            <div class="article-grid grid-3">
                <?php foreach ($latest as $article): ?>
                    <?php $card($article); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($popularTags)): ?>
    <section class="section tags">
        <div class="container">
            <h2 class="section__title"><?= e(__('label_tags')) ?></h2>
            <div class="tag-cloud">
                <?php foreach ($popularTags as $tag): ?>
                    <a class="tag-cloud__tag"
                       href="<?= e($searchBase . '?tag=' . rawurlencode((string) ($tag['slug'] ?? ''))) ?>">
                        <?= e((string) ($tag['name'] ?? '')) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
