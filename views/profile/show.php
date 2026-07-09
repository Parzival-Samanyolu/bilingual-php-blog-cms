<?php
/**
 * Public author profile page. Rendered in the main layout.
 * Minimal correct version created during Integration/QA (original was missing).
 *
 * @var array                 $profile   user row
 * @var array                 $articles  published article listing rows
 * @var array                 $stats     {article_count,total_views}
 * @var \App\Core\Pagination  $pagination
 */

$profile = $profile ?? [];
$name = (string) ($profile['name'] ?? $profile['username'] ?? '');
?>
<section class="profile" style="max-width:1000px;margin:0 auto;padding:1.5rem 1rem;">
  <header class="profile__head" style="display:flex;gap:1rem;align-items:center;margin-bottom:1.5rem;">
    <?php if (!empty($profile['avatar'])): ?>
      <img src="<?= e((string) $profile['avatar']) ?>" alt="" width="72" height="72"
           style="border-radius:50%;object-fit:cover;">
    <?php endif; ?>
    <div>
      <h1 style="margin:0;font-size:1.6rem;"><?= e($name) ?></h1>
      <p class="muted" style="margin:.25rem 0;">@<?= e((string) ($profile['username'] ?? '')) ?></p>
      <p class="muted" style="margin:0;font-size:.9rem;">
        <?= e(__('label_article_count', ['count' => (int) ($stats['article_count'] ?? 0)])) ?>
        &middot; <?= number_format((int) ($stats['total_views'] ?? 0)) ?> <?= e(__('label_views')) ?>
      </p>
    </div>
  </header>

  <?php if (!empty($profile['bio'])): ?>
    <p class="profile__bio" style="margin-bottom:1.5rem;"><?= e((string) $profile['bio']) ?></p>
  <?php endif; ?>

  <?php if ($articles === []): ?>
    <p class="empty muted"><?= e(__('no_results')) ?></p>
  <?php else: ?>
    <div class="article-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.25rem;">
      <?php foreach ($articles as $article): ?>
        <?= \App\Core\View::renderPartial('partials/article-card', ['article' => $article]) ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?= \App\Core\View::renderPartial('partials/pagination', ['pagination' => $pagination]) ?>
</section>
