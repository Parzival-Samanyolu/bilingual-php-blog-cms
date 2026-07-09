<?php
/**
 * Author article editor (create + edit) with a Quill rich-text field.
 * Minimal correct version created during Integration/QA (original was missing).
 *
 * @var string $mode        'create' | 'edit'
 * @var string $formAction  POST target
 * @var array  $article     assoc (title, content, excerpt, meta_title, meta_description, category_id, lang, cover_image, status)
 * @var string $tagsString  comma-separated tag names
 * @var array  $categories  [{id,label}, ...]
 */

use App\Core\Session;

$a = $article ?? [];
$content = (string) ($a['content'] ?? '');
$lang = (string) ($a['lang'] ?? \App\Core\Lang::getLang());
?>
<h1><?= e($mode === 'edit' ? __('editor_edit_title') : __('editor_new_title')) ?></h1>

<form method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data" class="card" id="articleForm">
  <input type="hidden" name="_csrf" value="<?= e(Session::getToken()) ?>">

  <div class="form-row">
    <label for="title"><?= e(__('admin_col_title')) ?></label>
    <input type="text" id="title" name="title" maxlength="500" required value="<?= e((string) ($a['title'] ?? '')) ?>">
  </div>

  <div class="form-row">
    <label for="category_id"><?= e(__('label_category')) ?></label>
    <select id="category_id" name="category_id" required>
      <option value=""><?= e(__('filter_all')) ?></option>
      <?php foreach ($categories as $opt): ?>
        <option value="<?= (int) $opt['id'] ?>" <?= (int) ($a['category_id'] ?? 0) === (int) $opt['id'] ? 'selected' : '' ?>><?= e((string) $opt['label']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-row">
    <label for="lang">Dil / Language</label>
    <select id="lang" name="lang">
      <option value="tr" <?= $lang === 'tr' ? 'selected' : '' ?>>Türkçe (TR)</option>
      <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>>English (EN)</option>
    </select>
  </div>

  <div class="form-row">
    <label>İçerik / Content</label>
    <div id="editor"><?= $content /* trusted author HTML re-hydrated into Quill */ ?></div>
    <textarea name="content" id="contentField" style="display:none;"><?= e($content) ?></textarea>
  </div>

  <div class="form-row">
    <label for="excerpt">Özet / Excerpt</label>
    <textarea id="excerpt" name="excerpt" maxlength="600"><?= e((string) ($a['excerpt'] ?? '')) ?></textarea>
  </div>

  <div class="form-row">
    <label for="tags"><?= e(__('label_tags')) ?></label>
    <input type="text" id="tags" name="tags" value="<?= e((string) ($tagsString ?? '')) ?>" placeholder="php, tarih, ...">
    <p class="hint">virgülle ayırın / comma separated</p>
  </div>

  <div class="form-row">
    <label for="cover">Kapak / Cover</label>
    <input type="file" id="cover" name="cover" accept="image/*">
    <?php if (!empty($a['cover_image'])): ?>
      <p class="hint"><img src="<?= e((string) $a['cover_image']) ?>" alt="" style="max-height:80px;border-radius:6px;"></p>
    <?php endif; ?>
  </div>

  <div class="form-row">
    <label for="meta_title">Meta title</label>
    <input type="text" id="meta_title" name="meta_title" maxlength="160" value="<?= e((string) ($a['meta_title'] ?? '')) ?>">
  </div>

  <div class="form-row">
    <label for="meta_description">Meta description</label>
    <textarea id="meta_description" name="meta_description" maxlength="320"><?= e((string) ($a['meta_description'] ?? '')) ?></textarea>
  </div>

  <button class="btn btn-primary" type="submit">Kaydet / Save</button>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
  if (typeof Quill === 'undefined') { return; }
  var holder = document.getElementById('editor');
  var field  = document.getElementById('contentField');
  var form   = document.getElementById('articleForm');
  var quill  = new Quill(holder, { theme: 'snow' });
  form.addEventListener('submit', function () {
    field.value = quill.root.innerHTML;
  });
});
</script>
