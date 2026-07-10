<?php
/**
 * Forgot-password form. Rendered in the main layout.
 *
 * @var array $old
 */

use App\Core\Session;

$old = $old ?? [];
?>
<div class="auth-page">
  <form method="post" action="/sifre-sifirla" class="auth-form">
    <h1><?= e(__('reset_password_title')) ?></h1>
    <p class="hint"><?= e(__('reset_password_intro')) ?></p>
    <input type="hidden" name="_csrf" value="<?= e(Session::getToken()) ?>">

    <div class="form-field">
      <label class="form-field__label" for="email"><?= e(__('label_email')) ?></label>
      <input class="form-field__input" id="email" type="email" name="email" required
             autocomplete="email" value="<?= e((string) ($old['email'] ?? '')) ?>">
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn--primary btn-block"><?= e(__('btn_send')) ?></button>
    </div>

    <p class="auth-links-row">
      <a href="/giris"><?= e(__('have_account')) ?></a>
    </p>
  </form>
</div>
