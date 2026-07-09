<?php
/**
 * Single article page.
 *
 * @var string                          $lang
 * @var array<string,mixed>             $article
 * @var array<int,array<string,mixed>>  $related
 * @var array<int,array<string,mixed>>  $tags
 * @var array<int,array<string,mixed>>  $comments
 * @var int                             $likeCount
 * @var bool                            $userLiked
 * @var bool                            $userBookmarked
 * @var bool                            $isLoggedIn
 */

use App\Core\Session;
use App\Core\View;

$catBase = $lang === 'en' ? '/category/' : '/kategori/';
$authorBase = '/yazar/';
$slug = (string) ($article['slug'] ?? '');
$catSlug = (string) ($article['category_slug'] ?? '');
$catName = (string) ($article['category_name'] ?? '');
$title = (string) ($article['title'] ?? '');
$cover = (string) ($article['cover_image'] ?? '');
$content = (string) ($article['content'] ?? '');
$authorName = (string) ($article['author_name'] ?? '');
$authorUsername = (string) ($article['author_username'] ?? '');
$views = (int) ($article['view_count'] ?? 0);

$createdAt = (string) ($article['created_at'] ?? '');
$ts = $createdAt !== '' ? strtotime($createdAt) : false;
$dateLabel = $ts !== false ? date($lang === 'en' ? 'M j, Y' : 'd.m.Y', $ts) : '';

$appUrl = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
$path = ($lang === 'en' ? '/article/' : '/yazi/') . rawurlencode($catSlug) . '/' . rawurlencode($slug);
$shareUrl = ($appUrl !== '' ? $appUrl : '') . $path;
$shareUrlEnc = rawurlencode($shareUrl);
$shareTitleEnc = rawurlencode($title);

/**
 * Recursively render a comment node and its replies.
 *
 * @param array<string,mixed> $node
 */
$renderComment = static function (array $node, string $lang) use (&$renderComment): void {
    $name = (string) ($node['user_name'] ?? $node['user_username'] ?? '');
    $avatar = (string) ($node['user_avatar'] ?? '');
    $body = (string) ($node['content'] ?? '');
    $cAt = (string) ($node['created_at'] ?? '');
    $cts = $cAt !== '' ? strtotime($cAt) : false;
    $cDate = $cts !== false ? date($lang === 'en' ? 'M j, Y' : 'd.m.Y', $cts) : '';
    ?>
    <li class="comment">
        <div class="comment__head">
            <?php if ($avatar !== ''): ?>
                <img class="comment__avatar" src="<?= e($avatar) ?>" alt="" width="36" height="36">
            <?php else: ?>
                <span class="comment__avatar comment__avatar--placeholder" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($name !== '' ? $name : 'U', 0, 1))) ?></span>
            <?php endif; ?>
            <span class="comment__author"><?= e($name) ?></span>
            <?php if ($cDate !== ''): ?>
                <span class="comment__date"><?= e($cDate) ?></span>
            <?php endif; ?>
        </div>
        <div class="comment__body"><?= nl2br(e($body)) ?></div>
        <?php if (!empty($node['children']) && is_array($node['children'])): ?>
            <ul class="comment__replies">
                <?php foreach ($node['children'] as $child): ?>
                    <?php $renderComment($child, $lang); ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </li>
    <?php
};
?>
<article class="article">
    <div class="container article__container">
        <nav class="breadcrumb" aria-label="breadcrumb">
            <a href="/"><?= e(__('nav_home')) ?></a>
            <?php if ($catName !== ''): ?>
                <span class="breadcrumb__sep">/</span>
                <a href="<?= e($catBase . rawurlencode($catSlug)) ?>"><?= e($catName) ?></a>
            <?php endif; ?>
            <span class="breadcrumb__sep">/</span>
            <span class="breadcrumb__current"><?= e($title) ?></span>
        </nav>

        <header class="article__header">
            <h1 class="article__title"><?= e($title) ?></h1>
            <div class="article__meta-bar">
                <?php if ($authorName !== ''): ?>
                    <span class="article__author">
                        <?php if ($authorUsername !== ''): ?>
                            <a href="<?= e($authorBase . rawurlencode($authorUsername)) ?>"><?= e($authorName) ?></a>
                        <?php else: ?>
                            <?= e($authorName) ?>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
                <?php if ($dateLabel !== ''): ?>
                    <span class="article__date"><?= e($dateLabel) ?></span>
                <?php endif; ?>
                <span class="article__views"><?= e((string) $views) ?> <?= e(__('label_views')) ?></span>
            </div>
        </header>

        <?php if ($cover !== ''): ?>
            <figure class="article__cover">
                <img src="<?= e($cover) ?>" alt="<?= e($title) ?>" loading="eager" width="1200" height="675">
            </figure>
        <?php endif; ?>

        <div class="article__content ql-editor">
            <?= $content /* sanitized by App\Core\HtmlSanitizer on write and on render */ ?>
        </div>

        <?php if (!empty($tags)): ?>
            <div class="article__tags">
                <span class="article__tags-label"><?= e(__('label_tags')) ?>:</span>
                <?php $searchBase = $lang === 'en' ? '/search' : '/ara'; ?>
                <?php foreach ($tags as $tag): ?>
                    <a class="tag-cloud__tag"
                       href="<?= e($searchBase . '?tag=' . rawurlencode((string) ($tag['slug'] ?? ''))) ?>">
                        <?= e((string) ($tag['name'] ?? '')) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="article__actions">
            <button type="button"
                    class="btn-like<?= $userLiked ? ' is-active' : '' ?>"
                    data-slug="<?= e($slug) ?>"
                    aria-pressed="<?= $userLiked ? 'true' : 'false' ?>">
                <span class="btn-like__icon" aria-hidden="true">&#9829;</span>
                <span class="btn-like__label"><?= e(__('btn_like')) ?></span>
                <span class="btn-like__count" data-like-count><?= e((string) $likeCount) ?></span>
            </button>

            <button type="button"
                    class="btn-bookmark<?= $userBookmarked ? ' is-active' : '' ?>"
                    data-slug="<?= e($slug) ?>"
                    aria-pressed="<?= $userBookmarked ? 'true' : 'false' ?>">
                <span class="btn-bookmark__icon" aria-hidden="true">&#128278;</span>
                <span class="btn-bookmark__label"><?= e(__('btn_bookmark')) ?></span>
            </button>

            <div class="article__share">
                <span class="article__share-label"><?= e(__('btn_share')) ?>:</span>
                <a class="share-link" rel="noopener" target="_blank"
                   href="https://twitter.com/intent/tweet?url=<?= e($shareUrlEnc) ?>&text=<?= e($shareTitleEnc) ?>">X</a>
                <a class="share-link" rel="noopener" target="_blank"
                   href="https://www.facebook.com/sharer/sharer.php?u=<?= e($shareUrlEnc) ?>">Facebook</a>
                <a class="share-link" rel="noopener" target="_blank"
                   href="https://api.whatsapp.com/send?text=<?= e($shareTitleEnc) ?>%20<?= e($shareUrlEnc) ?>">WhatsApp</a>
            </div>
        </div>
    </div>

    <?php if (!empty($related)): ?>
        <section class="section related">
            <div class="container">
                <h2 class="section__title"><?= e(__('related_articles')) ?></h2>
                <div class="article-grid grid-3 related__grid">
                    <?php foreach ($related as $rel): ?>
                        <?= View::renderPartial('partials/article-card', ['article' => $rel]) ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="section comments">
        <div class="container article__container">
            <h2 class="section__title comments__title">
                <?= e(__('label_comments')) ?>
            </h2>

            <?php if ($isLoggedIn): ?>
                <form class="comment-form js-comment-form" method="post"
                      action="<?= e('/yazi/' . rawurlencode($slug) . '/yorum') ?>"
                      data-slug="<?= e($slug) ?>">
                    <input type="hidden" name="_csrf" value="<?= e(Session::getToken()) ?>">
                    <label class="comment-form__label" for="comment-content"><?= e(__('leave_comment')) ?></label>
                    <textarea id="comment-content" class="comment-form__textarea" name="content"
                              rows="4" required maxlength="5000"
                              placeholder="<?= e(__('leave_comment')) ?>"></textarea>
                    <div class="comment-form__footer">
                        <span class="comment-form__note"><?= e(__('comment_moderation_note')) ?></span>
                        <button type="submit" class="btn btn--primary"><?= e(__('btn_submit_comment')) ?></button>
                    </div>
                    <p class="comment-form__feedback js-comment-feedback" role="status" hidden></p>
                </form>
            <?php else: ?>
                <p class="comments__login-prompt">
                    <a href="/giris"><?= e(__('login_to_comment')) ?></a>
                </p>
            <?php endif; ?>

            <?php if (empty($comments)): ?>
                <p class="comments__empty"><?= e(__('no_comments_yet')) ?></p>
            <?php else: ?>
                <ul class="comment-list">
                    <?php foreach ($comments as $comment): ?>
                        <?php $renderComment($comment, $lang); ?>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
</article>
