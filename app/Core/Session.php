<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Thin static wrapper around PHP native sessions.
 *
 * Provides secure session bootstrap, CSRF token management, one-shot flash
 * messages and authenticated-user shortcuts.
 */
final class Session
{
    /**
     * Start the session with hardened cookie settings.
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? null) == 443)
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_name('REALSESSID');
        session_start();
    }

    // ---------------------------------------------------------------------
    // Generic accessors
    // ---------------------------------------------------------------------

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    // ---------------------------------------------------------------------
    // CSRF
    // ---------------------------------------------------------------------

    /**
     * Generate (once per session) and return the CSRF token.
     */
    public static function generateToken(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf'];
    }

    /**
     * Return the current CSRF token, generating one if needed.
     */
    public static function getToken(): string
    {
        return self::generateToken();
    }

    /**
     * Constant-time comparison of a submitted token against the session token.
     */
    public static function validateToken(?string $token): bool
    {
        if (!is_string($token) || $token === '' || empty($_SESSION['csrf'])) {
            return false;
        }

        return hash_equals((string) $_SESSION['csrf'], $token);
    }

    // ---------------------------------------------------------------------
    // Flash messages (one-shot)
    // ---------------------------------------------------------------------

    public static function setFlash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    /**
     * Return and clear all pending flash messages.
     *
     * @return array<int, array{type:string, message:string}>
     */
    public static function getFlash(): array
    {
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        return $flash;
    }

    public static function hasFlash(): bool
    {
        return !empty($_SESSION['_flash']);
    }

    // ---------------------------------------------------------------------
    // Authenticated user shortcuts
    // ---------------------------------------------------------------------

    /**
     * @param array<string, mixed> $user
     */
    public static function setUser(array $user): void
    {
        $_SESSION['user'] = $user;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function clearUser(): void
    {
        unset($_SESSION['user']);
    }
}
