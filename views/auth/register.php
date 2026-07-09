<?php
/**
 * Registration form (reader or author). Rendered in the main layout.
 * Minimal correct version created during Integration/QA (original was missing).
 *
 * @var string $roleType  'reader' | 'author'
 * @var array  $old        previously submitted values
 */

use App\Core\Session;

$old = $old ?? [];
$roleType = ($roleType ?? 'reader') === 'author' ? 'author' : 'reader';
?>
<section class="auth-page" style="max-width:460px;margin:2.5rem auto;padding:0 1rem;">
  <h1 style="font-size:1.5rem;"><?= e(__('nav_register')) ?></h1>
  <?php if ($roleType === 'author'): ?>
    <p class="muted"><?= e(__('register_as_author')) ?></p>
  <?php endif; ?>

  <form method="post" action="/kayit" class="auth-form" style="display:flex;flex-direction:column;gap:1rem;margin-top:1rem;">
    <input type="hidden" name="_csrf" value="<?= e(Session::getToken()) ?>">
    <input type="hidden" name="role" value="<?= e($roleType) ?>">

    <label><span><?= e(__('contact_field_name')) ?></span>
      <input type="text" name="name" required maxlength="255" value="<?= e((string) ($old['name'] ?? '')) ?>"
             style="width:100%;padding:.6rem;border:1px solid #cbd5e1;border-radius:7px;"></label>

    <label><span>Kullanıcı adı / Username</span>
      <input type="text" name="username" required pattern="[a-z0-9_]{3,100}" value="<?= e((string) ($old['username'] ?? '')) ?>"
             style="width:100%;padding:.6rem;border:1px solid #cbd5e1;border-radius:7px;"></label>

    <label><span><?= e(__('contact_field_email')) ?></span>
      <input type="email" name="email" required autocomplete="email" value="<?= e((string) ($old['email'] ?? '')) ?>"
             style="width:100%;padding:.6rem;border:1px solid #cbd5e1;border-radius:7px;"></label>

    <label><span>Parola / Password</span>
      <input type="password" name="password" required minlength="8" autocomplete="new-password"
             style="width:100%;padding:.6rem;border:1px solid #cbd5e1;border-radius:7px;"></label>

    <label><span>Parola tekrar / Confirm password</span>
      <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password"
             style="width:100%;padding:.6rem;border:1px solid #cbd5e1;border-radius:7px;"></label>

    <button type="submit" class="btn btn--primary"
            style="padding:.65rem;background:#2563eb;color:#fff;border:0;border-radius:7px;cursor:pointer;font-size:1rem;">
      <?= e(__('nav_register')) ?>
    </button>
  </form>

  <p style="margin-top:1rem;"><a href="/giris"><?= e(__('nav_login')) ?></a></p>
</section>
