<?php
/**
 * Public author profile page. Rendered in the main layout.
 *
 * @var array                 $profile   user row
 * @var array                 $articles  published article listing rows
 * @var array                 $stats     {article_count,total_views}
 * @var \App\Core\Pagination  $pagination
 */

$profile = $profile ?? [];
$name = (string) ($profile['name'] ?? $profile['username'] ?? '');
$initial = mb_strtoupper(mb_substr($name !== '' ? $name : 'U', 0, 1));
?>
<div class="container profile">
  <header class="profile__head">
    <?php if (!empty($profile['avatar'])): ?>
      <img class="profile__avatar" src="<?= e((string) $profile['avatar']) ?>" alt="" width="72" height="72">
    <?php else: ?>
      <span class="profile__avatar profile__avatar--placeholder" aria-hidden="true"><?= e($initial) ?></span>
    <?php endif; ?>
    <div>
      <h1 class="profile__name"><?= e($name) ?></h1>
      <p class="profile__username">@<?= e((string) ($profile['username'] ?? '')) ?></p>
      <p class="profile__stats">
        <?= e(__('label_article_count', ['count' => (int) ($stats['article_count'] ?? 0)])) ?>
        &middot; <?= e(number_format((int) ($stats['total_views'] ?? 0))) ?> <?= e(__('label_views')) ?>
      </p>
    </div>
  </header>

  <?php if (!empty($profile['bio'])): ?>
    <p class="profile__bio"><?= e((string) $profile['bio']) ?></p>
  <?php endif; ?>

  <?php if ($articles === []): ?>
    <p class="empty-state"><?= e(__('no_results')) ?></p>
  <?php else: ?>
    <div class="article-grid">
      <?php foreach ($articles as $article): ?>
        <?= \App\Core\View::renderPartial('partials/article-card', ['article' => $article]) ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?= \App\Core\View::renderPartial('partials/pagination', ['pagination' => $pagination]) ?>
</div>
