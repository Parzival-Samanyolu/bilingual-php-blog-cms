<?php
/**
 * Admin tag management (bilingual, inline edit). Rendered in the admin layout.
 *
 * @var array $tags  [{id,slug,name_tr,name_en,article_count}, ...]
 */

use App\Core\Session;

$token = Session::getToken();
?>
<h1><?= e(__('admin_tags')) ?></h1>

<div class="card">
  <h2><?= e(__('admin_tag_new')) ?></h2>
  <form method="post" action="/admin/etiketler/yeni" class="inline-form">
    <input type="hidden" name="_csrf" value="<?= e($token) ?>">
    <div class="field"><label><?= e(__('admin_name_tr')) ?></label><input type="text" name="name_tr" required></div>
    <div class="field"><label><?= e(__('admin_name_en')) ?></label><input type="text" name="name_en"></div>
    <div class="field"><button class="btn btn-primary" type="submit"><?= e(__('admin_action_add')) ?></button></div>
  </form>
</div>

<?php if ($tags === []): ?>
  <p class="empty muted"><?= e(__('no_results')) ?></p>
<?php else: foreach ($tags as $t): $tid = (int) $t['id']; ?>
  <div class="card inline-form">
    <form method="post" action="/admin/etiketler/<?= $tid ?>" class="inline-form inline-form--grow">
      <input type="hidden" name="_csrf" value="<?= e($token) ?>">
      <div class="field"><label><?= e(__('admin_field_slug')) ?></label><input type="text" name="slug" value="<?= e((string) $t['slug']) ?>"></div>
      <div class="field"><label><?= e(__('admin_name_tr')) ?></label><input type="text" name="name_tr" value="<?= e((string) ($t['name_tr'] ?? '')) ?>" required></div>
      <div class="field"><label><?= e(__('admin_name_en')) ?></label><input type="text" name="name_en" value="<?= e((string) ($t['name_en'] ?? '')) ?>"></div>
      <div class="field"><label>&nbsp;</label><span class="muted"><?= e(__('label_article_count', ['count' => (int) ($t['article_count'] ?? 0)])) ?></span></div>
      <div class="field"><label>&nbsp;</label><button class="btn btn-primary btn-sm" type="submit"><?= e(__('btn_save')) ?></button></div>
    </form>
    <form method="post" action="/admin/etiketler/<?= $tid ?>/sil" class="inline-form" onsubmit="return confirm('<?= e(__('admin_confirm_delete')) ?>');">
      <input type="hidden" name="_csrf" value="<?= e($token) ?>">
      <button class="btn btn-danger btn-sm" type="submit"><?= e(__('admin_action_delete')) ?></button>
    </form>
  </div>
<?php endforeach; endif; ?>
