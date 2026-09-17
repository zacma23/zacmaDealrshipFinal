<?php

/**
 * Vercel Serverless Function Entry Point for Laravel 12
 */

// Set serverless environment flags
putenv('VERCEL=1');
$_ENV['VERCEL'] = '1';
$_SERVER['VERCEL'] = '1';

putenv('APP_STORAGE=/tmp/storage');
$_ENV['APP_STORAGE'] = '/tmp/storage';
$_SERVER['APP_STORAGE'] = '/tmp/storage';

putenv('LARAVEL_STORAGE_PATH=/tmp/storage');
$_ENV['LARAVEL_STORAGE_PATH'] = '/tmp/storage';
$_SERVER['LARAVEL_STORAGE_PATH'] = '/tmp/storage';

putenv('LOG_CHANNEL=stderr');
$_ENV['LOG_CHANNEL'] = 'stderr';
$_SERVER['LOG_CHANNEL'] = 'stderr';

putenv('CACHE_STORE=array');
$_ENV['CACHE_STORE'] = 'array';
$_SERVER['CACHE_STORE'] = 'array';

putenv('SESSION_DRIVER=cookie');
$_ENV['SESSION_DRIVER'] = 'cookie';
$_SERVER['SESSION_DRIVER'] = 'cookie';

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

if (empty($_ENV['APP_KEY']) || empty(getenv('APP_KEY'))) {
    $fallbackKey = 'base64:p7fVRPjNFEHr8eUHM9Mfsdd4Wl5XEsWkM5z8yvKI6l4=';
    putenv("APP_KEY={$fallbackKey}");
    $_ENV['APP_KEY'] = $fallbackKey;
    $_SERVER['APP_KEY'] = $fallbackKey;
}

if (empty($_ENV['DB_CONNECTION']) || empty(getenv('DB_CONNECTION'))) {
    putenv('DB_CONNECTION=sqlite');
    $_ENV['DB_CONNECTION'] = 'sqlite';
    $_SERVER['DB_CONNECTION'] = 'sqlite';
}

// Prepare serverless writable paths in /tmp
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
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Prepare SQLite database in /tmp if using SQLite
$dbConnection = getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? 'sqlite');
if ($dbConnection === 'sqlite') {
    $dbPath = '/tmp/database.sqlite';
    if (!file_exists($dbPath) || filesize($dbPath) === 0) {
        $starter = __DIR__ . '/../database/starter.sqlite';
        if (file_exists($starter)) {
            copy($starter, $dbPath);
        } else {
            touch($dbPath);
        }
    }
    @chmod($dbPath, 0666);
    putenv("DB_DATABASE={$dbPath}");
    $_ENV['DB_DATABASE'] = $dbPath;
    $_SERVER['DB_DATABASE'] = $dbPath;
}

// Forward to Laravel front controller
try {
    require __DIR__ . '/../public/index.php';
} catch (\Throwable $e) {
    http_response_code(500);
    echo "<h1>Vercel Boot Exception</h1>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
