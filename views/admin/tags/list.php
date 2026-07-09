<?php
/**
 * Admin tag management (bilingual, inline edit). Rendered in the admin layout.
 * Minimal correct version created during Integration/QA (original was missing).
 *
 * @var array $tags  [{id,slug,name_tr,name_en,article_count}, ...]
 */

use App\Core\Session;

$token = Session::getToken();
?>
<h1><?= e(__('admin_tags')) ?></h1>

<div class="card">
  <h2>Yeni etiket / New tag</h2>
  <form method="post" action="/admin/etiketler/yeni" class="filters" style="margin:0;border:0;padding:0;">
    <input type="hidden" name="_csrf" value="<?= e($token) ?>">
    <div class="field"><label>Ad (TR)</label><input type="text" name="name_tr" required></div>
    <div class="field"><label>Name (EN)</label><input type="text" name="name_en"></div>
    <div class="field"><button class="btn btn-primary" type="submit">Ekle / Add</button></div>
  </form>
</div>

<?php if ($tags === []): ?>
  <p class="empty muted"><?= e(__('no_results')) ?></p>
<?php else: foreach ($tags as $t): $tid = (int) $t['id']; ?>
  <div class="card" style="display:flex;gap:.6rem;align-items:end;flex-wrap:wrap;">
    <form method="post" action="/admin/etiketler/<?= $tid ?>" style="display:flex;gap:.6rem;align-items:end;flex-wrap:wrap;flex:1;margin:0;">
      <input type="hidden" name="_csrf" value="<?= e($token) ?>">
      <div class="field"><label>Slug</label><input type="text" name="slug" value="<?= e((string) $t['slug']) ?>"></div>
      <div class="field"><label>Ad (TR)</label><input type="text" name="name_tr" value="<?= e((string) ($t['name_tr'] ?? '')) ?>" required></div>
      <div class="field"><label>Name (EN)</label><input type="text" name="name_en" value="<?= e((string) ($t['name_en'] ?? '')) ?>"></div>
      <div class="field"><label>&nbsp;</label><span class="muted"><?= (int) ($t['article_count'] ?? 0) ?> yazı</span></div>
      <div class="field"><label>&nbsp;</label><button class="btn btn-primary btn-sm" type="submit">Kaydet</button></div>
    </form>
    <form method="post" action="/admin/etiketler/<?= $tid ?>/sil" style="margin:0;" onsubmit="return confirm('<?= e(__('admin_confirm_delete')) ?>');">
      <input type="hidden" name="_csrf" value="<?= e($token) ?>">
      <button class="btn btn-danger btn-sm" type="submit"><?= e(__('admin_action_delete')) ?></button>
    </form>
  </div>
<?php endforeach; endif; ?>
