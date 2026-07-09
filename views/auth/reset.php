<?php
/**
 * Password reset form (token-scoped). Rendered in the main layout.
 * Minimal correct version created during Integration/QA (original was missing).
 *
 * @var string $token
 */

use App\Core\Session;

$token = (string) ($token ?? '');
?>
<section class="auth-page" style="max-width:420px;margin:2.5rem auto;padding:0 1rem;">
  <h1 style="font-size:1.5rem;">Yeni parola / New password</h1>

  <form method="post" action="/sifre-yenile/<?= e(rawurlencode($token)) ?>" style="display:flex;flex-direction:column;gap:1rem;margin-top:1rem;">
    <input type="hidden" name="_csrf" value="<?= e(Session::getToken()) ?>">
    <label><span>Parola / Password</span>
      <input type="password" name="password" required minlength="8" autocomplete="new-password"
             style="width:100%;padding:.6rem;border:1px solid #cbd5e1;border-radius:7px;"></label>
    <label><span>Parola tekrar / Confirm password</span>
      <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password"
             style="width:100%;padding:.6rem;border:1px solid #cbd5e1;border-radius:7px;"></label>
    <button type="submit"
            style="padding:.65rem;background:#2563eb;color:#fff;border:0;border-radius:7px;cursor:pointer;font-size:1rem;">
      Kaydet / Save
    </button>
  </form>
</section>
