<?php
/**
 * Admin article editor (Quill), can reassign author.
 *
 * @var array $article
 * @var array $translation
 * @var string $tagString
 * @var array $categories  category tree
 * @var array $authors
 */

$flattenCats = static function (array $nodes, int $depth, callable $self): array {
    $out = [];
    foreach ($nodes as $node) {
        $out[] = ['id' => (int) $node['id'], 'name' => str_repeat('— ', $depth) . (string) $node['name']];
        if (!empty($node['children'])) {
            $out = array_merge($out, $self($node['children'], $depth + 1, $self));
        }
    }
    return $out;
};
$catOptions = $flattenCats($categories, 0, $flattenCats);
$content = (string) ($translation['content'] ?? '');
$aid = (int) $article['id'];
$statuses = ['draft', 'pending', 'published', 'rejected'];
?>
<h1><?= e(__('admin_article_edit')) ?></h1>

<form method="post" action="/admin/yazilar/<?= $aid ?>/duzenle" enctype="multipart/form-data" id="articleForm">
  <input type="hidden" name="_csrf" value="<?= e(\App\Core\Session::getToken()) ?>">

  <div class="grid-2">
    <div class="card">
      <div class="form-row">
        <label for="title"><?= e(__('admin_field_title')) ?></label>
        <input type="text" id="title" name="title" required value="<?= e($translation['title'] ?? '') ?>">
      </div>
      <div class="form-row">
        <label for="excerpt"><?= e(__('admin_field_excerpt')) ?></label>
        <textarea id="excerpt" name="excerpt"><?= e($translation['excerpt'] ?? '') ?></textarea>
      </div>
      <div class="form-row">
        <label><?= e(__('admin_field_content')) ?></label>
        <div id="admin-editor" style="background:#fff;min-height:260px;"><?= $content ?></div>
        <input type="hidden" name="content" id="content-input" value="<?= e($content) ?>">
      </div>
    </div>

    <div>
      <div class="card">
        <div class="form-row">
          <label for="status"><?= e(__('admin_col_status')) ?></label>
          <select id="status" name="status">
            <?php foreach ($statuses as $s): ?>
              <option value="<?= e($s) ?>" <?= ($article['status'] ?? '') === $s ? 'selected' : '' ?>><?= e(__('status_' . $s)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row">
          <label for="author_id"><?= e(__('admin_col_author')) ?></label>
          <select id="author_id" name="author_id">
            <?php foreach ($authors as $au): ?>
              <option value="<?= (int) $au['id'] ?>" <?= (int) ($article['author_id'] ?? 0) === (int) $au['id'] ? 'selected' : '' ?>><?= e($au['name']) ?> (@<?= e($au['username']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row">
          <label for="category_id"><?= e(__('admin_col_category')) ?></label>
          <select id="category_id" name="category_id">
            <?php foreach ($catOptions as $opt): ?>
              <option value="<?= (int) $opt['id'] ?>" <?= (int) ($article['category_id'] ?? 0) === $opt['id'] ? 'selected' : '' ?>><?= e($opt['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row">
          <label for="lang"><?= e(__('admin_col_lang')) ?></label>
          <select id="lang" name="lang">
            <option value="tr" <?= ($article['lang'] ?? 'tr') === 'tr' ? 'selected' : '' ?>>TR</option>
            <option value="en" <?= ($article['lang'] ?? 'tr') === 'en' ? 'selected' : '' ?>>EN</option>
          </select>
        </div>
        <div class="form-row">
          <label for="slug"><?= e(__('admin_field_slug')) ?></label>
          <input type="text" id="slug" name="slug" value="<?= e($article['slug'] ?? '') ?>">
        </div>
        <div class="form-row">
          <label for="tags"><?= e(__('admin_field_tags')) ?></label>
          <input type="text" id="tags" name="tags" value="<?= e($tagString) ?>" placeholder="<?= e(__('admin_field_tags_hint')) ?>">
        </div>
      </div>

      <div class="card">
        <div class="form-row">
          <label for="cover_image"><?= e(__('admin_field_cover')) ?></label>
          <?php if (!empty($article['cover_image'])): ?>
            <img src="<?= e($article['cover_image']) ?>" alt="" style="max-width:100%;border-radius:8px;margin-bottom:.6rem;">
          <?php endif; ?>
          <input type="file" id="cover_image" name="cover_image" accept="image/*">
          <p class="muted"><?= e(__('admin_field_cover_hint')) ?></p>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <h2><?= e(__('admin_seo_section')) ?></h2>
    <div class="form-row">
      <label for="meta_title"><?= e(__('admin_field_meta_title')) ?></label>
      <input type="text" id="meta_title" name="meta_title" maxlength="160" value="<?= e($translation['meta_title'] ?? '') ?>">
    </div>
    <div class="form-row">
      <label for="meta_description"><?= e(__('admin_field_meta_description')) ?></label>
      <textarea id="meta_description" name="meta_description" maxlength="320"><?= e($translation['meta_description'] ?? '') ?></textarea>
    </div>
  </div>

  <div style="display:flex;gap:.75rem;margin-bottom:2rem;">
    <button class="btn btn-primary" type="submit"><?= e(__('admin_action_save')) ?></button>
    <a class="btn" href="/admin/yazilar"><?= e(__('admin_action_cancel')) ?></a>
  </div>
</form>

<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
(function () {
  var host = document.getElementById('admin-editor');
  if (!host || typeof Quill === 'undefined') { return; }
  var quill = new Quill('#admin-editor', {
    theme: 'snow',
    modules: {
      toolbar: [
        [{ header: [2, 3, 4, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        ['blockquote', 'code-block'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['link', 'image'],
        ['clean']
      ]
    }
  });
  var form = document.getElementById('articleForm');
  var hidden = document.getElementById('content-input');
  form.addEventListener('submit', function () {
    hidden.value = quill.root.innerHTML;
  });
})();
</script>
