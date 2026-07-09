<?php
/**
 * Shared sidebar: category tree + popular tag cloud.
 *
 * Optional variables (fetched on demand when not supplied by the caller):
 *   $categoryTree — nested category nodes from CategoryModel::getTree()
 *   $popularTags  — rows from TagModel::getPopular()
 *
 * @var array<int,array<string,mixed>>|null $categoryTree
 * @var array<int,array<string,mixed>>|null $popularTags
 */

use App\Core\Lang;
use App\Models\CategoryModel;
use App\Models\TagModel;

$sidebarLang = Lang::getLang();
$tree = isset($categoryTree) && is_array($categoryTree)
    ? $categoryTree
    : (new CategoryModel())->getTree($sidebarLang);
$tags = isset($popularTags) && is_array($popularTags)
    ? $popularTags
    : (new TagModel())->getPopular($sidebarLang, 20);

$catBase = $sidebarLang === 'en' ? '/category/' : '/kategori/';
$searchBase = $sidebarLang === 'en' ? '/search' : '/ara';

/**
 * Render a category branch recursively.
 *
 * @param array<int,array<string,mixed>> $nodes
 */
$renderBranch = static function (array $nodes, string $catBase) use (&$renderBranch): void {
    echo '<ul class="sidebar__cat-list">';
    foreach ($nodes as $node) {
        $url = $catBase . rawurlencode((string) ($node['slug'] ?? ''));
        echo '<li class="sidebar__cat-item">';
        echo '<a href="' . e($url) . '">' . e((string) ($node['name'] ?? '')) . '</a>';
        if (!empty($node['children']) && is_array($node['children'])) {
            $renderBranch($node['children'], $catBase);
        }
        echo '</li>';
    }
    echo '</ul>';
};
?>
<aside class="sidebar">
    <section class="sidebar__section">
        <h2 class="sidebar__heading"><?= e(__('label_category')) ?></h2>
        <?php if ($tree === []): ?>
            <p class="sidebar__empty"><?= e(__('no_results')) ?></p>
        <?php else: ?>
            <?php $renderBranch($tree, $catBase); ?>
        <?php endif; ?>
    </section>

    <?php if ($tags !== []): ?>
        <section class="sidebar__section">
            <h2 class="sidebar__heading"><?= e(__('label_tags')) ?></h2>
            <div class="tag-cloud">
                <?php foreach ($tags as $tag): ?>
                    <a class="tag-cloud__tag"
                       href="<?= e($searchBase . '?tag=' . rawurlencode((string) ($tag['slug'] ?? ''))) ?>">
                        <?= e((string) ($tag['name'] ?? '')) ?>
                        <?php if (isset($tag['article_count'])): ?>
                            <span class="tag-cloud__count"><?= e((string) (int) $tag['article_count']) ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</aside>
