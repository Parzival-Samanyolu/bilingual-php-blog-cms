<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Authentication service: local (email + Argon2id) and Google OAuth 2.0,
 * plus role checks and IP-based login rate limiting.
 */
final class Auth
{
    private const OAUTH_TOKEN_URL    = 'https://oauth2.googleapis.com/token';
    private const OAUTH_USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';
    private const OAUTH_AUTH_URL     = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const MAX_ATTEMPTS  = 5;
    private const WINDOW_MINUTES = 15;

    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ---------------------------------------------------------------------
    // Local authentication
    // ---------------------------------------------------------------------

    /**
     * Verify credentials and, on success, establish the session.
     */
    public function login(string $email, string $password): bool
    {
        $user = $this->db->fetch(
            'SELECT * FROM users WHERE email = ? LIMIT 1',
            [$email]
        );

        if ($user === null || empty($user['password_hash'])) {
            return false;
        }

        // Banned accounts (is_approved = -1) must never authenticate.
        if ((int) ($user['is_approved'] ?? 0) === -1) {
            return false;
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        // Transparently upgrade the hash if the algorithm/cost changed.
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_ARGON2ID)) {
            $newHash = password_hash($password, PASSWORD_ARGON2ID);
            $this->db->execute(
                'UPDATE users SET password_hash = ? WHERE id = ?',
                [$newHash, $user['id']]
            );
        }

        session_regenerate_id(true);
        Session::setUser($this->publicUser($user));

        return true;
    }

    public function logout(): void
    {
        Session::clearUser();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires'  => time() - 42000,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Create a new local user and return its id.
     */
    public function register(
        string $name,
        string $username,
        string $email,
        string $password,
        string $role = 'reader'
    ): int {
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        // Readers are usable immediately; authors await admin approval.
        $isApproved = $role === 'reader' ? 1 : 0;

        $this->db->execute(
            'INSERT INTO users (name, username, email, password_hash, role, is_approved, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [$name, $username, $email, $hash, $role, $isApproved]
        );

        return $this->db->lastInsertId();
    }

    // ---------------------------------------------------------------------
    // Google OAuth 2.0
    // ---------------------------------------------------------------------

    /**
     * Generate an anti-forgery state token and persist it in the session.
     */
    public function googleState(): string
    {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        return $state;
    }

    public function validateGoogleState(?string $state): bool
    {
        $stored = $_SESSION['oauth_state'] ?? null;
        unset($_SESSION['oauth_state']);

        if (!is_string($state) || $state === '' || !is_string($stored)) {
            return false;
        }

        return hash_equals($stored, $state);
    }

    /**
     * Build the Google authorization URL to redirect the user to.
     */
    public function googleRedirectUrl(): string
    {
        $params = [
            'client_id'     => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
            'redirect_uri'  => $_ENV['GOOGLE_REDIRECT_URI'] ?? '',
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'access_type'   => 'online',
            'prompt'        => 'select_account',
            'state'         => $this->googleState(),
        ];

        return self::OAUTH_AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Exchange an authorization code for a profile, upsert the user and return
     * the stored user row.
     *
     * @return array<string, mixed>
     */
    public function googleCallback(string $code): array
    {
        $token = $this->httpPost(self::OAUTH_TOKEN_URL, [
            'code'          => $code,
            'client_id'     => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
            'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
            'redirect_uri'  => $_ENV['GOOGLE_REDIRECT_URI'] ?? '',
            'grant_type'    => 'authorization_code',
        ]);

        if (!isset($token['access_token'])) {
            throw new RuntimeException('Google token exchange failed.');
        }

        $profile = $this->httpGet(
            self::OAUTH_USERINFO_URL,
            ['Authorization: Bearer ' . $token['access_token']]
        );

        $googleId = $profile['sub'] ?? null;
        $email    = $profile['email'] ?? null;
        if ($googleId === null || $email === null) {
            throw new RuntimeException('Google profile response was incomplete.');
        }

        $name   = $profile['name'] ?? $email;
        $avatar = $profile['picture'] ?? null;

        // Google returns the verification flag as `email_verified` (v3 userinfo)
        // or `verified_email` (older). Both may arrive as bool or string.
        $emailVerified = filter_var(
            $profile['email_verified'] ?? $profile['verified_email'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $user = $this->upsertGoogleUser(
            (string) $googleId,
            (string) $email,
            (string) $name,
            $avatar,
            $emailVerified
        );

        session_regenerate_id(true);
        Session::setUser($this->publicUser($user));

        return $user;
    }

    /**
     * Find-or-create a user for the given Google identity.
     *
     * @return array<string, mixed>
     */
    private function upsertGoogleUser(
        string $googleId,
        string $email,
        string $name,
        ?string $avatar,
        bool $emailVerified = false
    ): array {
        $existing = $this->db->fetch('SELECT * FROM users WHERE google_id = ? LIMIT 1', [$googleId]);

        if ($existing !== null) {
            $this->db->execute(
                'UPDATE users SET name = ?, avatar = COALESCE(?, avatar), updated_at = NOW() WHERE id = ?',
                [$name, $avatar, $existing['id']]
            );

            return $this->db->fetch('SELECT * FROM users WHERE id = ? LIMIT 1', [$existing['id']]) ?? $existing;
        }

        // Anything below keys off the email address, which is only trustworthy
        // when Google reports it as verified — otherwise refuse to link or create
        // (prevents takeover of a local account via an unverified Google email).
        if (!$emailVerified) {
            throw new RuntimeException('Google account email is not verified.');
        }

        // A local account may already own this email — link the Google identity.
        $byEmail = $this->db->fetch('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
        if ($byEmail !== null) {
            $this->db->execute(
                'UPDATE users
                    SET google_id = ?,
                        avatar = COALESCE(avatar, ?),
                        email_verified_at = COALESCE(email_verified_at, NOW()),
                        updated_at = NOW()
                  WHERE id = ?',
                [$googleId, $avatar, $byEmail['id']]
            );

            return $this->db->fetch('SELECT * FROM users WHERE id = ? LIMIT 1', [$byEmail['id']]) ?? $byEmail;
        }

        $username = $this->uniqueUsername($email);
        $this->db->execute(
            'INSERT INTO users
                (name, username, email, google_id, avatar, role, is_approved, email_verified_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())',
            [$name, $username, $email, $googleId, $avatar, 'reader', 1]
        );
        $id = $this->db->lastInsertId();

        return $this->db->fetch('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]) ?? [];
    }

    /**
     * Derive a unique username from the email local-part.
     */
    private function uniqueUsername(string $email): string
    {
        $base = preg_replace('/[^a-z0-9_]+/', '', strtolower(explode('@', $email)[0]));
        if ($base === '' || $base === null) {
            $base = 'user';
        }
        $base = substr($base, 0, 90);

        $candidate = $base;
        $suffix    = 0;
        while ($this->db->fetch('SELECT id FROM users WHERE username = ? LIMIT 1', [$candidate]) !== null) {
            $suffix++;
            $candidate = $base . $suffix;
        }

        return $candidate;
    }

    // ---------------------------------------------------------------------
    // Role & state checks
    // ---------------------------------------------------------------------

    public function isLoggedIn(): bool
    {
        return Session::getUser() !== null;
    }

    public function isAdmin(): bool
    {
        return ($this->current()['role'] ?? null) === 'admin';
    }

    public function isAuthor(): bool
    {
        $role = $this->current()['role'] ?? null;

        return $role === 'author' || $role === 'admin';
    }

    public function isApprovedAuthor(): bool
    {
        $user = $this->current();
        if (($user['role'] ?? null) === 'admin') {
            return true;
        }

        return ($user['role'] ?? null) === 'author' && (int) ($user['is_approved'] ?? 0) === 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function user(): array
    {
        return $this->current();
    }

    public function id(): ?int
    {
        $id = $this->current()['id'] ?? null;

        return $id === null ? null : (int) $id;
    }

    // ---------------------------------------------------------------------
    // Login rate limiting
    // ---------------------------------------------------------------------

    /**
     * True when the IP is still under the attempt threshold (login allowed).
     */
    public function checkLoginRateLimit(string $ip): bool
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS c FROM login_attempts
              WHERE ip = ? AND attempted_at >= (NOW() - INTERVAL ? MINUTE)',
            [$ip, self::WINDOW_MINUTES]
        );

        return (int) ($row['c'] ?? 0) < self::MAX_ATTEMPTS;
    }

    public function recordLoginAttempt(string $ip): void
    {
        $this->db->execute(
            'INSERT INTO login_attempts (ip, attempted_at) VALUES (?, NOW())',
            [$ip]
        );
    }

    public function clearLoginAttempts(string $ip): void
    {
        $this->db->execute('DELETE FROM login_attempts WHERE ip = ?', [$ip]);
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function current(): array
    {
        return Session::getUser() ?? [];
    }

    /**
     * Reduce a DB user row to the safe subset stored in the session.
     *
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    private function publicUser(array $user): array
    {
        return [
            'id'             => (int) ($user['id'] ?? 0),
            'name'           => $user['name'] ?? '',
            'username'       => $user['username'] ?? '',
            'email'          => $user['email'] ?? '',
            'role'           => $user['role'] ?? 'reader',
            'avatar'         => $user['avatar'] ?? null,
            'is_approved'    => (int) ($user['is_approved'] ?? 0),
            'preferred_lang' => $user['preferred_lang'] ?? 'tr',
        ];
    }

    /**
     * @param array<string, string> $fields
     * @return array<string, mixed>
     */
    private function httpPost(string $url, array $fields): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Google request failed: ' . $error);
        }

        $decoded = json_decode((string) $response, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<int, string> $headers
     * @return array<string, mixed>
     */
    private function httpGet(string $url, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Google request failed: ' . $error);
        }

        $decoded = json_decode((string) $response, true);

        return is_array($decoded) ? $decoded : [];
    }
}
