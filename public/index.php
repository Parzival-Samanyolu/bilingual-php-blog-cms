<?php

declare(strict_types=1);

/**
 * Front controller for real.com.tr
 *
 * Loads the environment, registers a PSR-4 autoloader for the App\ namespace,
 * wires up the global helper functions, loads the route table and dispatches.
 */

// Works whether the project is laid out as public/ + ../app (dev) or flattened
// into a single web directory (shared-host subfolder deploy).
define('BASE_PATH', is_dir(__DIR__ . '/app') ? __DIR__ : dirname(__DIR__));

// ---------------------------------------------------------------------------
// 1. Environment (.env) — manual key=value parser, no Composer required.
// ---------------------------------------------------------------------------
(static function (): void {
    $envFile = BASE_PATH . '/.env';
    if (!is_file($envFile)) {
        return;
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || $line[0] === ';') {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Strip optional surrounding quotes.
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last  = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        if ($key === '') {
            continue;
        }

        $_ENV[$key]    = $value;
        $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
    }
})();

// ---------------------------------------------------------------------------
// 1b. Base path — set APP_BASE (e.g. "/yeni") when hosted under a subfolder so
//     the router can strip it and generated URLs can be prefixed. Empty at root.
// ---------------------------------------------------------------------------
define('APP_BASE', rtrim((string) ($_ENV['APP_BASE'] ?? ''), '/'));

$currentPath = static function (): string {
    $p = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (APP_BASE !== '' && str_starts_with($p, APP_BASE)) {
        $p = substr($p, strlen(APP_BASE));
    }
    return $p === '' ? '/' : $p;
};

// Local dev only: let the PHP built-in server serve real static files directly
// (base-aware) instead of routing them through the front controller. Inert under
// Apache/cPanel (mod_rewrite already excludes real files).
if (PHP_SAPI === 'cli-server') {
    $rel = $currentPath();
    if ($rel !== '/' && is_file(__DIR__ . $rel)) {
        return false;
    }
}

// ---------------------------------------------------------------------------
// 2. Error reporting based on APP_ENV.
// ---------------------------------------------------------------------------
$appEnv = $_ENV['APP_ENV'] ?? 'production';
if ($appEnv === 'development' || $appEnv === 'local') {
    // Show real problems, but not deprecation noise from running under a newer
    // PHP than the 8.2 target (e.g. PDO constant deprecations on PHP 8.5).
    error_reporting(E_ALL & ~E_DEPRECATED);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// ---------------------------------------------------------------------------
// 3. PSR-4 autoloader: prefix "App\" maps to the app/ directory.
// ---------------------------------------------------------------------------
spl_autoload_register(static function (string $class): void {
    $prefix  = 'App\\';
    $baseDir = BASE_PATH . '/app/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file     = $baseDir . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

// ---------------------------------------------------------------------------
// 4. Explicitly load the core files that define global helper functions.
//    (e() lives in View.php, __() in Lang.php, route() in Router.php.)
// ---------------------------------------------------------------------------
require BASE_PATH . '/app/Core/View.php';
require BASE_PATH . '/app/Core/Lang.php';
require BASE_PATH . '/app/Core/Router.php';

use App\Core\Lang;
use App\Core\Router;
use App\Core\Session;

// ---------------------------------------------------------------------------
// 5. Session + language bootstrap.
// ---------------------------------------------------------------------------
Session::start();
Lang::getLang();

// ---------------------------------------------------------------------------
// 6. Router: instantiate, load routes, dispatch.
// ---------------------------------------------------------------------------
$router = new Router();

$routesFile = BASE_PATH . '/app/routes.php';
if (is_file($routesFile)) {
    // routes.php receives $router in scope to register routes.
    require $routesFile;
}

try {
    $router->dispatch(null, $currentPath());
} catch (\Throwable $e) {
    if ($appEnv === 'development' || $appEnv === 'local') {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo '500 Internal Server Error' . PHP_EOL . PHP_EOL;
        echo $e->getMessage() . PHP_EOL;
        echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
        echo $e->getTraceAsString() . PHP_EOL;
    } else {
        error_log((string) $e);
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        echo '<h1>500 Internal Server Error</h1>';
    }
}
