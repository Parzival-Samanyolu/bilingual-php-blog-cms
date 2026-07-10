<?php
/**
 * Registration form (reader or author). Rendered in the main layout.
 *
 * @var string $roleType  'reader' | 'author'
 * @var array  $old        previously submitted values
 */

use App\Core\Session;

$old = $old ?? [];
$roleType = ($roleType ?? 'reader') === 'author' ? 'author' : 'reader';
?>
<div class="auth-page">
  <form method="post" action="/kayit" class="auth-form">
    <h1><?= e(__('register_title')) ?></h1>
    <?php if ($roleType === 'author'): ?>
      <p class="hint"><?= e(__('register_role_hint')) ?></p>
    <?php endif; ?>
    <input type="hidden" name="_csrf" value="<?= e(Session::getToken()) ?>">
    <input type="hidden" name="role" value="<?= e($roleType) ?>">

    <div class="form-field">
      <label class="form-field__label" for="name"><?= e(__('label_name')) ?></label>
      <input class="form-field__input" id="name" type="text" name="name" required maxlength="255"
             value="<?= e((string) ($old['name'] ?? '')) ?>">
    </div>

    <div class="form-field">
      <label class="form-field__label" for="username"><?= e(__('label_username')) ?></label>
      <input class="form-field__input" id="username" type="text" name="username" required
             pattern="[a-z0-9_]{3,100}" value="<?= e((string) ($old['username'] ?? '')) ?>">
    </div>

    <div class="form-field">
      <label class="form-field__label" for="email"><?= e(__('label_email')) ?></label>
      <input class="form-field__input" id="email" type="email" name="email" required
             autocomplete="email" value="<?= e((string) ($old['email'] ?? '')) ?>">
    </div>

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
      <button type="submit" class="btn btn--primary btn-block"><?= e(__('btn_register')) ?></button>
    </div>

    <p class="auth-links-row">
      <a href="/giris"><?= e(__('have_account')) ?></a>
    </p>
  </form>
</div>
