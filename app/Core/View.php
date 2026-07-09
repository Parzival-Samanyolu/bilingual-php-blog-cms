<?php

declare(strict_types=1);

namespace App\Core {

    use RuntimeException;

    /**
     * Minimal PHP template renderer.
     *
     * A template is rendered to a string ($content) which is then embedded in a
     * layout. Templates and layouts are plain PHP files under views/.
     */
    final class View
    {
        /**
         * Render a template inside a layout.
         *
         * @param string               $template slash path under views/ without .php
         * @param array<string, mixed> $data     variables exposed to template & layout
         * @param string               $layout   layout name under views/layouts/
         */
        public static function render(string $template, array $data = [], string $layout = 'main'): string
        {
            $content = self::renderPartial($template, $data);

            if ($layout === '' ) {
                echo $content;

                return $content;
            }

            $layoutFile = self::viewsPath() . '/layouts/' . $layout . '.php';
            if (!is_file($layoutFile)) {
                throw new RuntimeException("Layout not found: {$layout}");
            }

            $output = (static function () use ($layoutFile, $data, $content): string {
                extract($data, EXTR_SKIP);
                /** @var string $content */
                ob_start();
                include $layoutFile;

                return (string) ob_get_clean();
            })();

            $output = self::withBasePath($output);

            echo $output;

            return $output;
        }

        /**
         * Render a template to a string without a layout.
         *
         * @param array<string, mixed> $data
         */
        public static function renderPartial(string $template, array $data = []): string
        {
            $file = self::viewsPath() . '/' . ltrim($template, '/') . '.php';
            if (!is_file($file)) {
                throw new RuntimeException("View not found: {$template}");
            }

            return (static function () use ($file, $data): string {
                extract($data, EXTR_SKIP);
                ob_start();
                include $file;

                return (string) ob_get_clean();
            })();
        }

        /**
         * Emit a JSON response and terminate.
         *
         * @param mixed $data
         */
        public static function json(mixed $data, int $status = 200): void
        {
            if (!headers_sent()) {
                http_response_code($status);
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        /**
         * Send a Location redirect and terminate.
         */
        public static function redirect(string $url, int $status = 302): void
        {
            if (!headers_sent()) {
                http_response_code($status);
                header('Location: ' . $url);
            }
            exit;
        }

        private static function viewsPath(): string
        {
            return dirname(__DIR__, 2) . '/views';
        }

        /**
         * When the app is hosted under a subfolder (APP_BASE, e.g. "/yeni"),
         * prefix every root-absolute URL in the rendered HTML — href/src/action
         * and the /uploads paths inside article content — so links and assets
         * resolve under the subfolder. Protocol-relative ("//cdn") URLs are left
         * untouched. No-op at the domain root (APP_BASE empty).
         */
        private static function withBasePath(string $html): string
        {
            if (!defined('APP_BASE') || APP_BASE === '') {
                return $html;
            }

            return (string) preg_replace(
                '#\b(href|src|action|data-src|poster|formaction)="/(?!/)#i',
                '$1="' . APP_BASE . '/',
                $html
            );
        }
    }
}

namespace {

    if (!function_exists('e')) {
        /**
         * Escape a value for safe HTML output. Null-safe.
         */
        function e(mixed $str): string
        {
            if ($str === null) {
                return '';
            }

            return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
        }
    }
}
