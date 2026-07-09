<?php

declare(strict_types=1);

namespace App\Core {

    /**
     * Translation registry and language detection.
     *
     * Detection order: an explicit runtime override (e.g. URL hint) →
     * $_SESSION['lang'] → the "lang" cookie → the Accept-Language header → 'tr'.
     */
    final class Lang
    {
        private const SUPPORTED = ['tr', 'en'];
        private const DEFAULT   = 'tr';

        /** @var array<string, string> */
        private static array $strings = [];

        private static ?string $current = null;

        private static ?string $loadedFor = null;

        /**
         * Resolve, cache and return the active language code.
         */
        public static function getLang(): string
        {
            if (self::$current !== null) {
                return self::$current;
            }

            $lang = null;

            if (isset($_SESSION['lang']) && self::isSupported((string) $_SESSION['lang'])) {
                $lang = (string) $_SESSION['lang'];
            } elseif (isset($_COOKIE['lang']) && self::isSupported((string) $_COOKIE['lang'])) {
                $lang = (string) $_COOKIE['lang'];
            } else {
                $lang = self::fromAcceptLanguage();
            }

            self::$current = $lang ?? self::DEFAULT;

            return self::$current;
        }

        /**
         * Persist a language choice (session + cookie) and load its strings.
         */
        public static function setLang(string $lang): void
        {
            if (!self::isSupported($lang)) {
                $lang = self::DEFAULT;
            }

            self::$current       = $lang;
            $_SESSION['lang']    = $lang;

            if (!headers_sent()) {
                setcookie('lang', $lang, [
                    'expires'  => time() + 60 * 60 * 24 * 365,
                    'path'     => '/',
                    'secure'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                    'httponly' => false,
                    'samesite' => 'Lax',
                ]);
            }

            self::load($lang);
        }

        /**
         * Override the active language for this request only (no persistence).
         * Used by the router when a URL implies a language.
         */
        public static function useLang(string $lang): void
        {
            if (self::isSupported($lang)) {
                self::$current = $lang;
                self::load($lang);
            }
        }

        /**
         * Look up a translation key, applying {placeholder} replacements.
         *
         * @param array<string, string|int> $replace
         */
        public static function get(string $key, array $replace = []): string
        {
            self::ensureLoaded();

            $value = self::$strings[$key] ?? $key;

            foreach ($replace as $name => $replacement) {
                $value = str_replace('{' . $name . '}', (string) $replacement, $value);
            }

            return $value;
        }

        /**
         * @return array<string, string>
         */
        public static function all(): array
        {
            self::ensureLoaded();

            return self::$strings;
        }

        private static function ensureLoaded(): void
        {
            $lang = self::getLang();
            if (self::$loadedFor !== $lang) {
                self::load($lang);
            }
        }

        private static function load(string $lang): void
        {
            $file = dirname(__DIR__, 2) . '/lang/' . $lang . '.php';
            if (is_file($file)) {
                $strings = require $file;
                self::$strings = is_array($strings) ? $strings : [];
            } else {
                self::$strings = [];
            }
            self::$loadedFor = $lang;
        }

        private static function isSupported(string $lang): bool
        {
            return in_array($lang, self::SUPPORTED, true);
        }

        private static function fromAcceptLanguage(): ?string
        {
            $header = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
            if ($header === '') {
                return self::DEFAULT;
            }

            foreach (explode(',', $header) as $part) {
                $code = strtolower(trim(substr($part, 0, 2)));
                if (self::isSupported($code)) {
                    return $code;
                }
            }

            return self::DEFAULT;
        }
    }
}

namespace {

    if (!function_exists('__')) {
        /**
         * Global translation helper with {placeholder} replacement, falling
         * back to the key itself when no translation exists.
         *
         * @param array<string, string|int> $replace
         */
        function __(string $key, array $replace = []): string
        {
            return \App\Core\Lang::get($key, $replace);
        }
    }
}
