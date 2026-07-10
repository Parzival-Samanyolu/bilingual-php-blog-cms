<?php
/**
 * Admin site settings editor. Rendered in the admin layout.
 *
 * @var array $settings  key => value map
 */

use App\Core\Session;

$s = static fn (string $k, string $d = ''): string => (string) ($settings[$k] ?? $d);
?>
<h1><?= e(__('admin_settings')) ?></h1>

<form method="post" action="/admin/ayarlar" enctype="multipart/form-data" class="card">
  <input type="hidden" name="_csrf" value="<?= e(Session::getToken()) ?>">

  <div class="grid-2">
    <div class="form-row">
      <label for="site_name_tr"><?= e(__('setting_site_name_tr')) ?></label>
      <input type="text" id="site_name_tr" name="site_name_tr" value="<?= e($s('site_name_tr')) ?>">
    </div>
    <div class="form-row">
      <label for="site_name_en"><?= e(__('setting_site_name_en')) ?></label>
      <input type="text" id="site_name_en" name="site_name_en" value="<?= e($s('site_name_en')) ?>">
    </div>
  </div>

  <div class="form-row">
    <label for="contact_email"><?= e(__('setting_contact_email')) ?></label>
    <input type="email" id="contact_email" name="contact_email" value="<?= e($s('contact_email')) ?>">
  </div>

  <div class="grid-2">
    <div class="form-row">
      <label for="ga_measurement_id"><?= e(__('setting_ga_id')) ?></label>
      <input type="text" id="ga_measurement_id" name="ga_measurement_id" value="<?= e($s('ga_measurement_id')) ?>" placeholder="G-XXXXXXX">
    </div>
    <div class="form-row">
      <label for="adsense_client_id"><?= e(__('setting_adsense_id')) ?></label>
      <input type="text" id="adsense_client_id" name="adsense_client_id" value="<?= e($s('adsense_client_id')) ?>" placeholder="ca-pub-...">
    </div>
  </div>

  <div class="grid-2">
    <div class="form-row">
      <label for="articles_per_page"><?= e(__('setting_articles_per_page')) ?></label>
      <input type="number" id="articles_per_page" name="articles_per_page" min="1" value="<?= e($s('articles_per_page', '12')) ?>">
    </div>
    <div class="form-row">
      <label for="cache_ttl"><?= e(__('setting_cache_ttl')) ?></label>
      <input type="number" id="cache_ttl" name="cache_ttl" min="0" value="<?= e($s('cache_ttl', '3600')) ?>">
    </div>
  </div>

  <div class="form-row">
    <label for="og_default_image"><?= e(__('setting_og_image')) ?></label>
    <input type="text" name="og_default_image" value="<?= e($s('og_default_image')) ?>">
    <p class="muted"><?= e(__('setting_upload_new')) ?></p>
    <input type="file" name="og_default_image" accept="image/*">
  </div>

  <button class="btn btn-primary" type="submit"><?= e(__('btn_save')) ?></button>
</form>
