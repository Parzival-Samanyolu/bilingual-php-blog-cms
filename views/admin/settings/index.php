<?php
/**
 * Admin site settings editor. Rendered in the admin layout.
 * Minimal correct version created during Integration/QA (original was missing).
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
      <label for="site_name_tr">Site adı (TR)</label>
      <input type="text" id="site_name_tr" name="site_name_tr" value="<?= e($s('site_name_tr')) ?>">
    </div>
    <div class="form-row">
      <label for="site_name_en">Site name (EN)</label>
      <input type="text" id="site_name_en" name="site_name_en" value="<?= e($s('site_name_en')) ?>">
    </div>
  </div>

  <div class="form-row">
    <label for="contact_email">İletişim e-postası / Contact email</label>
    <input type="email" id="contact_email" name="contact_email" value="<?= e($s('contact_email')) ?>">
  </div>

  <div class="grid-2">
    <div class="form-row">
      <label for="ga_measurement_id">Google Analytics ID</label>
      <input type="text" id="ga_measurement_id" name="ga_measurement_id" value="<?= e($s('ga_measurement_id')) ?>" placeholder="G-XXXXXXX">
    </div>
    <div class="form-row">
      <label for="adsense_client_id">AdSense client ID</label>
      <input type="text" id="adsense_client_id" name="adsense_client_id" value="<?= e($s('adsense_client_id')) ?>" placeholder="ca-pub-...">
    </div>
  </div>

  <div class="grid-2">
    <div class="form-row">
      <label for="articles_per_page">Sayfa başına yazı / Articles per page</label>
      <input type="number" id="articles_per_page" name="articles_per_page" min="1" value="<?= e($s('articles_per_page', '12')) ?>">
    </div>
    <div class="form-row">
      <label for="cache_ttl">Cache TTL (sn / sec)</label>
      <input type="number" id="cache_ttl" name="cache_ttl" min="0" value="<?= e($s('cache_ttl', '3600')) ?>">
    </div>
  </div>

  <div class="form-row">
    <label for="og_default_image">OG default image</label>
    <input type="text" name="og_default_image" value="<?= e($s('og_default_image')) ?>">
    <p class="muted" style="margin:.35rem 0 0;">Yeni görsel yükle / upload new:</p>
    <input type="file" name="og_default_image" accept="image/*">
  </div>

  <button class="btn btn-primary" type="submit">Kaydet / Save</button>
</form>
