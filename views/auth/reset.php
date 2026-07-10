<?php
/**
 * Password reset form (token-scoped). Rendered in the main layout.
 *
 * @var string $token
 */

use App\Core\Session;

$token = (string) ($token ?? '');
?>
<div class="auth-page">
  <form method="post" action="/sifre-yenile/<?= e(rawurlencode($token)) ?>" class="auth-form">
    <h1><?= e(__('new_password_title')) ?></h1>
    <input type="hidden" name="_csrf" value="<?= e(Session::getToken()) ?>">

    <div class="form-field">
      <label class="form-field__label" for="password"><?= e(__('label_password')) ?></label>
      <input class="form-field__input" id="password" type="password" name="password" required
             minlength="8" autocomplete="new-password">
    </div>

    <div class="form-field">
      <label class="form-field__label" for="password_confirm"><?= e(__('label_password_confirm')) ?></label>
      <input class="form-field__input" id="password_confirm" type="password" name="password_confirm"
             required minlength="8" autocomplete="new-password">
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn--primary btn-block"><?= e(__('btn_save')) ?></button>
    </div>
  </form>
</div>
