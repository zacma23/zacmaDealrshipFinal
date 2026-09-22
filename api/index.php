<?php

/**
 * Vercel Serverless Function Entry Point for Laravel 12
 */

// 1. Ensure REMOTE_ADDR is populated to prevent Symfony IpUtils::checkIp4 fatal TypeError
if (empty($_SERVER['REMOTE_ADDR'])) {
    $remoteIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? '127.0.0.1';
    if (str_contains($remoteIp, ',')) {
        $remoteIp = trim(explode(',', $remoteIp)[0]);
    }
    $_SERVER['REMOTE_ADDR'] = $remoteIp ?: '127.0.0.1';
}

// 2. Set serverless environment flags & root script name
putenv('VERCEL=1');
$_ENV['VERCEL'] = '1';
$_SERVER['VERCEL'] = '1';

// Normalize SCRIPT_NAME and PHP_SELF so Symfony/Laravel treats this as the root front-controller.
// This prevents Symfony Request::prepareBaseUrl from treating '/api' as the base URL,
// which would incorrectly strip '/api' from routes and prepend '/api' to Vite asset URLs.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';


// 3. Storage and Cache paths in writable /tmp directory
putenv('APP_STORAGE=/tmp/storage');
$_ENV['APP_STORAGE'] = '/tmp/storage';
$_SERVER['APP_STORAGE'] = '/tmp/storage';

putenv('LARAVEL_STORAGE_PATH=/tmp/storage');
$_ENV['LARAVEL_STORAGE_PATH'] = '/tmp/storage';
$_SERVER['LARAVEL_STORAGE_PATH'] = '/tmp/storage';

putenv('APP_CONFIG_CACHE=/tmp/config.php');
$_ENV['APP_CONFIG_CACHE'] = '/tmp/config.php';
$_SERVER['APP_CONFIG_CACHE'] = '/tmp/config.php';

putenv('APP_EVENTS_CACHE=/tmp/events.php');
$_ENV['APP_EVENTS_CACHE'] = '/tmp/events.php';
$_SERVER['APP_EVENTS_CACHE'] = '/tmp/events.php';

putenv('APP_PACKAGES_CACHE=/tmp/packages.php');
$_ENV['APP_PACKAGES_CACHE'] = '/tmp/packages.php';
$_SERVER['APP_PACKAGES_CACHE'] = '/tmp/packages.php';

putenv('APP_ROUTES_CACHE=/tmp/routes.php');
$_ENV['APP_ROUTES_CACHE'] = '/tmp/routes.php';
$_SERVER['APP_ROUTES_CACHE'] = '/tmp/routes.php';

putenv('APP_SERVICES_CACHE=/tmp/services.php');
$_ENV['APP_SERVICES_CACHE'] = '/tmp/services.php';
$_SERVER['APP_SERVICES_CACHE'] = '/tmp/services.php';

putenv('VIEW_COMPILED_PATH=/tmp/storage/framework/views');
$_ENV['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';
$_SERVER['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';

// 4. Serverless drivers
//
// This front controller only ever runs inside the Vercel serverless function,
// whose filesystem is read-only (except /tmp) and which has no long-running
// worker. Any project-level env var that points session/cache/queue at a
// stateful backend (a `database`/`redis` driver, a persistent file store, etc.)
// will make ordinary requests try to reach a backend that isn't reliably
// available here and return a 500. Force stateless, serverless-safe drivers
// UNCONDITIONALLY so dashboard values cannot override them at runtime.
$forceEnv = static function (string $key, string $value): void {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
};

// Logs must go to stderr (captured by Vercel); no writable log file exists.
$forceEnv('LOG_CHANNEL', 'stderr');

// Cache: in-request array store. There is no shared cache backend guaranteed
// to be reachable, and the default `database` store requires a cache table.
$forceEnv('CACHE_STORE', 'array');

// Sessions: encrypted cookie store keeps sessions stateless across cold starts.
$forceEnv('SESSION_DRIVER', 'cookie');

// Queue: run jobs inline. There is no worker to drain a `database`/`redis` queue.
$forceEnv('QUEUE_CONNECTION', 'sync');

putenv('SESSION_LIFETIME=120');
$_ENV['SESSION_LIFETIME'] = '120';
$_SERVER['SESSION_LIFETIME'] = '120';

putenv('SESSION_PATH=/');
$_ENV['SESSION_PATH'] = '/';
$_SERVER['SESSION_PATH'] = '/';

putenv('SESSION_COOKIE=zacma_session');
$_ENV['SESSION_COOKIE'] = 'zacma_session';
$_SERVER['SESSION_COOKIE'] = 'zacma_session';

if (isset($_ENV['SESSION_DOMAIN']) && trim((string)$_ENV['SESSION_DOMAIN']) === '') {
    unset($_ENV['SESSION_DOMAIN'], $_SERVER['SESSION_DOMAIN']);
    putenv('SESSION_DOMAIN');
}

putenv('APP_MAINTENANCE_DRIVER=file');
$_ENV['APP_MAINTENANCE_DRIVER'] = 'file';
$_SERVER['APP_MAINTENANCE_DRIVER'] = 'file';

// 5. App Encryption Key
$appKey = getenv('APP_KEY') ?: ($_ENV['APP_KEY'] ?? null);
if (empty($appKey)) {
    $fallbackKey = 'base64:p7fVRPjNFEHr8eUHM9Mfsdd4Wl5XEsWkM5z8yvKI6l4=';
    putenv("APP_KEY={$fallbackKey}");
    $_ENV['APP_KEY'] = $fallbackKey;
    $_SERVER['APP_KEY'] = $fallbackKey;
}

// 6. Database Connection defaults
$dbConnection = getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? null);
if (empty($dbConnection)) {
    putenv('DB_CONNECTION=sqlite');
    $_ENV['DB_CONNECTION'] = 'sqlite';
    $_SERVER['DB_CONNECTION'] = 'sqlite';
    $dbConnection = 'sqlite';
}

// 7. Prepare serverless writable paths in /tmp
$dirs = [
    '/tmp/storage',
    '/tmp/storage/framework',
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
    '/tmp/storage/app',
    '/tmp/storage/app/public',
    '/tmp/cache',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// 8. Prepare SQLite database in /tmp if using SQLite
if ($dbConnection === 'sqlite') {
    $dbPath = '/tmp/database.sqlite';
    $starter = __DIR__ . '/../database/starter.sqlite';
    if (file_exists($starter) && filesize($starter) > 0) {
        if (!file_exists($dbPath) || filesize($dbPath) === 0 || filemtime($starter) > filemtime($dbPath)) {
            copy($starter, $dbPath);
        }
    } elseif (!file_exists($dbPath) || filesize($dbPath) === 0) {
        touch($dbPath);
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            $app = require_once __DIR__ . '/../bootstrap/app.php';
            $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
            $kernel->call('migrate', ['--force' => true]);
            $kernel->call('db:seed', ['--force' => true]);
        } catch (\Throwable $migErr) {
            // Fallback touched empty db
        }
    }
    @chmod($dbPath, 0666);
    putenv("DB_DATABASE={$dbPath}");
    $_ENV['DB_DATABASE'] = $dbPath;
    $_SERVER['DB_DATABASE'] = $dbPath;
}

// 9. Forward to Laravel front controller
try {
    require __DIR__ . '/../public/index.php';
} catch (\Throwable $e) {
    http_response_code(500);
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    echo "<h1>Vercel Boot Exception</h1>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
