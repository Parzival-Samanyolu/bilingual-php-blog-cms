<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Models\UserModel;
use Throwable;

/**
 * Authentication flows: local login/registration, Google OAuth 2.0,
 * logout and the password-reset lifecycle.
 *
 * Route handlers only; route registration lives in app/routes.php.
 */
final class AuthController extends BaseController
{
    private const RESET_TTL_MINUTES = 60;

    // ------------------------------------------------------------------
    // Login
    // ------------------------------------------------------------------

    public function loginForm(): void
    {
        if ($this->auth()->isLoggedIn()) {
            $this->redirect('/');
        }

        $this->view('auth/login', [
            'title' => __('login_title'),
            'old'   => $this->oldInput(),
        ]);
    }

    public function login(): void
    {
        $this->validateCsrf();

        $email    = $this->input('email');
        $password = $_POST['password'] ?? '';
        $ip       = $this->clientIp();

        if ($email === '' || !is_string($password) || $password === '') {
            Session::setFlash('error', __('flash_login_missing_fields'));
            $this->flashInput(['email' => $email]);
            $this->redirect('/giris');

            return;
        }

        if (!$this->auth()->checkLoginRateLimit($ip)) {
            Session::setFlash('error', __('flash_login_rate_limited'));
            $this->flashInput(['email' => $email]);
            $this->redirect('/giris');

            return;
        }

        if (!$this->auth()->login($email, (string) $password)) {
            $this->auth()->recordLoginAttempt($ip);
            Session::setFlash('error', __('flash_login_failed'));
            $this->flashInput(['email' => $email]);
            $this->redirect('/giris');

            return;
        }

        $this->auth()->clearLoginAttempts($ip);
        Session::setFlash('success', __('flash_login_success'));

        $intended = Session::get('_intended');
        Session::remove('_intended');
        $target = is_string($intended) && $intended !== '' && str_starts_with($intended, '/')
            ? $intended
            : '/';

        $this->redirect($target);
    }

    // ------------------------------------------------------------------
    // Registration
    // ------------------------------------------------------------------

    public function registerForm(): void
    {
        if ($this->auth()->isLoggedIn()) {
            $this->redirect('/');
        }

        $type = ($_GET['type'] ?? '') === 'author' ? 'author' : 'reader';

        $this->view('auth/register', [
            'title'    => __('register_title'),
            'roleType' => $type,
            'old'      => $this->oldInput(),
        ]);
    }

    public function register(): void
    {
        $this->validateCsrf();

        $name     = $this->input('name');
        $username = strtolower($this->input('username'));
        $email    = $this->input('email');
        $password = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['password_confirm'] ?? '');
        $wantsAuthor = ($_POST['role'] ?? ($_GET['type'] ?? '')) === 'author';

        $errors = [];
        if ($name === '' || mb_strlen($name) > 255) {
            $errors[] = __('validation_name');
        }
        if (!preg_match('/^[a-z0-9_]{3,100}$/', $username)) {
            $errors[] = __('validation_username');
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('validation_email');
        }
        if (mb_strlen($password) < 8) {
            $errors[] = __('validation_password_length');
        }
        if ($password !== $confirm) {
            $errors[] = __('validation_password_confirm');
        }

        $users = new UserModel();
        if ($email !== '' && $users->findByEmail($email) !== null) {
            $errors[] = __('validation_email_taken');
        }
        if ($username !== '' && $users->findByUsername($username) !== null) {
            $errors[] = __('validation_username_taken');
        }

        if ($errors !== []) {
            foreach ($errors as $error) {
                Session::setFlash('error', $error);
            }
            $this->flashInput([
                'name'     => $name,
                'username' => $username,
                'email'    => $email,
                'role'     => $wantsAuthor ? 'author' : 'reader',
            ]);
            $redirect = $wantsAuthor ? '/kayit?type=author' : '/kayit';
            $this->redirect($redirect);

            return;
        }

        $role = $wantsAuthor ? 'author' : 'reader';
        $this->auth()->register($name, $username, $email, $password, $role);

        Session::setFlash(
            'success',
            $wantsAuthor ? __('flash_register_author_pending') : __('flash_register_success')
        );

        $this->redirect('/giris');
    }

    // ------------------------------------------------------------------
    // Google OAuth 2.0
    // ------------------------------------------------------------------

    public function googleRedirect(): void
    {
        // googleRedirectUrl() also stores the anti-forgery state in the session.
        $this->redirect($this->auth()->googleRedirectUrl());
    }

    public function googleCallback(): void
    {
        $state = isset($_GET['state']) && is_string($_GET['state']) ? $_GET['state'] : null;
        $code  = isset($_GET['code']) && is_string($_GET['code']) ? $_GET['code'] : null;

        if (isset($_GET['error']) || $code === null) {
            Session::setFlash('error', __('flash_google_failed'));
            $this->redirect('/giris');

            return;
        }

        if (!$this->auth()->validateGoogleState($state)) {
            Session::setFlash('error', __('flash_google_state'));
            $this->redirect('/giris');

            return;
        }

        try {
            $this->auth()->googleCallback($code);
        } catch (Throwable $e) {
            Session::setFlash('error', __('flash_google_failed'));
            $this->redirect('/giris');

            return;
        }

        Session::setFlash('success', __('flash_login_success'));
        $this->redirect('/');
    }

    // ------------------------------------------------------------------
    // Logout
    // ------------------------------------------------------------------

    public function logout(): void
    {
        $this->auth()->logout();
        Session::setFlash('success', __('flash_logout'));
        $this->redirect('/');
    }

    // ------------------------------------------------------------------
    // Password reset
    // ------------------------------------------------------------------

    public function forgotForm(): void
    {
        $this->view('auth/forgot', [
            'title' => __('forgot_title'),
            'old'   => $this->oldInput(),
        ]);
    }

    public function sendReset(): void
    {
        $this->validateCsrf();

        $email = $this->input('email');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::setFlash('error', __('validation_email'));
            $this->flashInput(['email' => $email]);
            $this->redirect('/sifre-sifirla');

            return;
        }

        $users = new UserModel();
        $user  = $users->findByEmail($email);

        // Only issue a token for real, password-capable accounts, but always
        // show the same confirmation to avoid account enumeration.
        if ($user !== null && !empty($user['password_hash'])) {
            $token   = bin2hex(random_bytes(32));
            $expires = (new \DateTimeImmutable('+' . self::RESET_TTL_MINUTES . ' minutes'))
                ->format('Y-m-d H:i:s');

            $db = \App\Core\Database::getInstance();
            $db->execute(
                'INSERT INTO password_resets (email, token, expires_at, used) VALUES (?, ?, ?, 0)',
                [$email, $token, $expires]
            );

            $this->sendResetEmail($email, $token);
        }

        Session::setFlash('success', __('flash_reset_sent'));
        $this->redirect('/giris');
    }

    public function resetForm(string $token): void
    {
        if ($this->findValidReset($token) === null) {
            Session::setFlash('error', __('flash_reset_invalid'));
            $this->redirect('/sifre-sifirla');

            return;
        }

        $this->view('auth/reset', [
            'title' => __('reset_title'),
            'token' => $token,
        ]);
    }

    public function resetPassword(string $token): void
    {
        $this->validateCsrf();

        $reset = $this->findValidReset($token);
        if ($reset === null) {
            Session::setFlash('error', __('flash_reset_invalid'));
            $this->redirect('/sifre-sifirla');

            return;
        }

        $password = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['password_confirm'] ?? '');

        $errors = [];
        if (mb_strlen($password) < 8) {
            $errors[] = __('validation_password_length');
        }
        if ($password !== $confirm) {
            $errors[] = __('validation_password_confirm');
        }

        if ($errors !== []) {
            foreach ($errors as $error) {
                Session::setFlash('error', $error);
            }
            $this->redirect('/sifre-yenile/' . rawurlencode($token));

            return;
        }

        $db   = \App\Core\Database::getInstance();
        $hash = password_hash($password, PASSWORD_ARGON2ID);

        $db->beginTransaction();
        try {
            $db->execute(
                'UPDATE users SET password_hash = ?, updated_at = NOW() WHERE email = ?',
                [$hash, $reset['email']]
            );
            $db->execute('UPDATE password_resets SET used = 1 WHERE id = ?', [$reset['id']]);
            // Invalidate any other outstanding tokens for this email.
            $db->execute(
                'UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0',
                [$reset['email']]
            );
            $db->commit();
        } catch (Throwable $e) {
            $db->rollback();
            throw $e;
        }

        Session::setFlash('success', __('flash_reset_success'));
        $this->redirect('/giris');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Return an unused, unexpired reset row for the token, or null.
     *
     * @return array<string, mixed>|null
     */
    private function findValidReset(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        return \App\Core\Database::getInstance()->fetch(
            'SELECT * FROM password_resets
              WHERE token = ? AND used = 0 AND expires_at >= NOW()
              LIMIT 1',
            [$token]
        );
    }

    /**
     * Compose and send the password-reset email via native mail().
     */
    private function sendResetEmail(string $email, string $token): void
    {
        $base = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
        $link = $base . '/sifre-yenile/' . rawurlencode($token);

        $subject = __('reset_email_subject');
        $body    = __('reset_email_body', ['link' => $link]);

        $from    = (string) ($_ENV['MAIL_FROM'] ?? 'no-reply@example.com');
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $from,
        ];

        // Encode the subject for non-ASCII safety.
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        @mail($email, $encodedSubject, $body, implode("\r\n", $headers));
    }
}
