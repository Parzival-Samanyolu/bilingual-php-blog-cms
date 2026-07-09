<?php
/**
 * Reusable article card.
 *
 * Expected variable:
 *   $article — assoc array with keys: title, excerpt, slug, category_slug,
 *              category_name, cover_image, author_name, created_at, view_count
 *
 * @var array<string,mixed> $article
 */

use App\Core\Lang;

if (!isset($article) || !is_array($article)) {
    return;
}

$cardLang = Lang::getLang();
$articleUrl = ($cardLang === 'en' ? '/article/' : '/yazi/')
    . rawurlencode((string) ($article['category_slug'] ?? ''))
    . '/' . rawurlencode((string) ($article['slug'] ?? ''));
$categoryUrl = ($cardLang === 'en' ? '/category/' : '/kategori/')
    . rawurlencode((string) ($article['category_slug'] ?? ''));

$cover   = (string) ($article['cover_image'] ?? '');
$title   = (string) ($article['title'] ?? '');
$excerpt = (string) ($article['excerpt'] ?? '');
$author  = (string) ($article['author_name'] ?? '');
$views   = (int) ($article['view_count'] ?? 0);
$catName = (string) ($article['category_name'] ?? '');

$createdAt = (string) ($article['created_at'] ?? '');
$ts = $createdAt !== '' ? strtotime($createdAt) : false;
$dateLabel = $ts !== false
    ? date($cardLang === 'en' ? 'M j, Y' : 'd.m.Y', $ts)
    : '';
?>
<article class="article-card">
    <a class="article-card__media" href="<?= e($articleUrl) ?>">
        <?php if ($cover !== ''): ?>
            <img class="article-card__cover" src="<?= e($cover) ?>" alt="<?= e($title) ?>" loading="lazy" width="640" height="360">
        <?php else: ?>
            <span class="article-card__cover article-card__cover--empty" aria-hidden="true"></span>
        <?php endif; ?>
        <?php if ($catName !== ''): ?>
            <span class="article-card__badge"><?= e($catName) ?></span>
        <?php endif; ?>
    </a>
    <div class="article-card__body">
        <h3 class="article-card__title">
            <a href="<?= e($articleUrl) ?>"><?= e($title) ?></a>
        </h3>
        <?php if ($excerpt !== ''): ?>
            <p class="article-card__excerpt"><?= e(mb_strimwidth($excerpt, 0, 160, '…')) ?></p>
        <?php endif; ?>
        <div class="article-card__meta">
            <?php if ($author !== ''): ?>
                <span class="article-card__author"><?= e($author) ?></span>
            <?php endif; ?>
            <?php if ($dateLabel !== ''): ?>
                <span class="article-card__date"><?= e($dateLabel) ?></span>
            <?php endif; ?>
            <span class="article-card__views"><?= e((string) $views) ?> <?= e(__('label_views')) ?></span>
        </div>
    </div>
</article>
