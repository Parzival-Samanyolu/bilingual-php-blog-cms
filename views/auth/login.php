<?php
/**
 * Login form. Rendered in the main layout.
 * Minimal correct version created during Integration/QA (original was missing).
 *
 * @var array $old  previously submitted values (email)
 */

use App\Core\Session;

$old = $old ?? [];
?>
<section class="auth-page" style="max-width:420px;margin:2.5rem auto;padding:0 1rem;">
  <h1 style="font-size:1.5rem;"><?= e(__('nav_login')) ?></h1>

  <form method="post" action="/giris" class="auth-form" style="display:flex;flex-direction:column;gap:1rem;margin-top:1rem;">
    <input type="hidden" name="_csrf" value="<?= e(Session::getToken()) ?>">

    <label>
      <span><?= e(__('contact_field_email')) ?></span>
      <input type="email" name="email" required autocomplete="email"
             value="<?= e((string) ($old['email'] ?? '')) ?>"
             style="width:100%;padding:.6rem;border:1px solid #cbd5e1;border-radius:7px;">
    </label>

    <label>
      <span>Parola / Password</span>
      <input type="password" name="password" required autocomplete="current-password"
             style="width:100%;padding:.6rem;border:1px solid #cbd5e1;border-radius:7px;">
    </label>

    <button type="submit" class="btn btn--primary"
            style="padding:.65rem;background:#2563eb;color:#fff;border:0;border-radius:7px;cursor:pointer;font-size:1rem;">
      <?= e(__('nav_login')) ?>
    </button>
  </form>

  <p style="margin-top:1rem;">
    <a href="/auth/google">Google</a>
    &middot; <a href="/sifre-sifirla">Parolamı unuttum / Forgot password</a>
  </p>
  <p><a href="/kayit"><?= e(__('nav_register')) ?></a></p>
</section>
