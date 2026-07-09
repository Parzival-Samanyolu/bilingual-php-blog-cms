<?php
/**
 * Forgot-password form. Rendered in the main layout.
 * Minimal correct version created during Integration/QA (original was missing).
 *
 * @var array $old
 */

use App\Core\Session;

$old = $old ?? [];
?>
<section class="auth-page" style="max-width:420px;margin:2.5rem auto;padding:0 1rem;">
  <h1 style="font-size:1.5rem;">Parola sıfırlama / Reset password</h1>

  <form method="post" action="/sifre-sifirla" style="display:flex;flex-direction:column;gap:1rem;margin-top:1rem;">
    <input type="hidden" name="_csrf" value="<?= e(Session::getToken()) ?>">
    <label><span><?= e(__('contact_field_email')) ?></span>
      <input type="email" name="email" required autocomplete="email" value="<?= e((string) ($old['email'] ?? '')) ?>"
             style="width:100%;padding:.6rem;border:1px solid #cbd5e1;border-radius:7px;"></label>
    <button type="submit"
            style="padding:.65rem;background:#2563eb;color:#fff;border:0;border-radius:7px;cursor:pointer;font-size:1rem;">
      Gönder / Send
    </button>
  </form>

  <p style="margin-top:1rem;"><a href="/giris"><?= e(__('nav_login')) ?></a></p>
</section>
