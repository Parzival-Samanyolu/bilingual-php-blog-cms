<?php
/**
 * Login form. Rendered in the main layout.
 *
 * @var array $old  previously submitted values (email)
 */

use App\Core\Session;

$old = $old ?? [];
?>
<div class="auth-page">
  <form method="post" action="/giris" class="auth-form">
    <h1><?= e(__('login_title')) ?></h1>
    <input type="hidden" name="_csrf" value="<?= e(Session::getToken()) ?>">

    <div class="form-field">
      <label class="form-field__label" for="email"><?= e(__('label_email')) ?></label>
      <input class="form-field__input" id="email" type="email" name="email" required
             autocomplete="email" value="<?= e((string) ($old['email'] ?? '')) ?>">
    </div>

    <div class="form-field">
      <label class="form-field__label" for="password"><?= e(__('label_password')) ?></label>
      <input class="form-field__input" id="password" type="password" name="password" required
             autocomplete="current-password">
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn--primary btn-block"><?= e(__('btn_login')) ?></button>
    </div>

    <p class="auth-sep"><span><?= e(__('or_continue_with')) ?></span></p>
    <a class="btn btn-secondary btn-block" href="/auth/google"><?= e(__('login_with_google')) ?></a>

    <p class="auth-links-row">
      <a href="/sifre-sifirla"><?= e(__('forgot_password')) ?></a>
      <a href="/kayit"><?= e(__('no_account')) ?></a>
    </p>
  </form>
</div>
