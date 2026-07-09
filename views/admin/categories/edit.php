<?php
/**
 * Admin category create/edit form (TR + EN).
 *
 * @var array|null $category
 * @var array      $translations  ['tr'=>row,'en'=>row]
 * @var array      $parents       [ ['id'=>int,'name'=>string], ... ]
 * @var string     $formAction
 */
$tr = $translations['tr'] ?? [];
$en = $translations['en'] ?? [];
$currentParent = $category['parent_id'] ?? null;
?>
<h1><?= e($category === null ? __('admin_category_new') : __('admin_category_edit')) ?></h1>

<form method="post" action="<?= e($formAction) ?>">
  <input type="hidden" name="_csrf" value="<?= e(\App\Core\Session::getToken()) ?>">

  <div class="grid-2">
    <div class="card">
      <h2>Türkçe (TR)</h2>
      <div class="form-row">
        <label for="name_tr"><?= e(__('admin_field_name')) ?></label>
        <input type="text" id="name_tr" name="name_tr" required value="<?= e($tr['name'] ?? '') ?>">
      </div>
      <div class="form-row">
        <label for="description_tr"><?= e(__('admin_field_description')) ?></label>
        <textarea id="description_tr" name="description_tr"><?= e($tr['description'] ?? '') ?></textarea>
      </div>
    </div>
    <div class="card">
      <h2>English (EN)</h2>
      <div class="form-row">
        <label for="name_en"><?= e(__('admin_field_name')) ?></label>
        <input type="text" id="name_en" name="name_en" value="<?= e($en['name'] ?? '') ?>">
      </div>
      <div class="form-row">
        <label for="description_en"><?= e(__('admin_field_description')) ?></label>
        <textarea id="description_en" name="description_en"><?= e($en['description'] ?? '') ?></textarea>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="grid-2">
      <div class="form-row">
        <label for="parent_id"><?= e(__('admin_field_parent')) ?></label>
        <select id="parent_id" name="parent_id">
          <option value=""><?= e(__('admin_field_no_parent')) ?></option>
          <?php foreach ($parents as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= (int) ($currentParent ?? 0) === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <label for="sort_order"><?= e(__('admin_col_sort')) ?></label>
        <input type="number" id="sort_order" name="sort_order" value="<?= (int) ($category['sort_order'] ?? 0) ?>">
      </div>
    </div>
    <div class="form-row">
      <label for="slug"><?= e(__('admin_field_slug')) ?></label>
      <input type="text" id="slug" name="slug" value="<?= e($category['slug'] ?? '') ?>" placeholder="<?= e(__('admin_slug_auto_hint')) ?>">
    </div>
  </div>

  <div style="display:flex;gap:.75rem;margin-bottom:2rem;">
    <button class="btn btn-primary" type="submit"><?= e(__('admin_action_save')) ?></button>
    <a class="btn" href="/admin/kategoriler"><?= e(__('admin_action_cancel')) ?></a>
  </div>
</form>
