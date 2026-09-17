<?php

use App\Http\Middleware\CheckRoleMiddleware;
use App\Http\Middleware\EnforceSubscriptionLimitsMiddleware;
use App\Http\Middleware\TenantResolverMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            TenantResolverMiddleware::class,
        ]);

        $middleware->alias([
            'role' => CheckRoleMiddleware::class,
            'plan.limit' => EnforceSubscriptionLimitsMiddleware::class,
            'tenant' => TenantResolverMiddleware::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'api/webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

// Ensure writable storage path on Vercel / AWS Lambda Serverless
if (
    !empty($_ENV['VERCEL']) ||
    !empty($_SERVER['VERCEL']) ||
    !empty($_ENV['NOW_REGION']) ||
    !empty($_SERVER['NOW_REGION']) ||
    !empty($_ENV['AWS_LAMBDA_FUNCTION_NAME']) ||
    getenv('APP_STORAGE')
) {
    $storage = getenv('APP_STORAGE') ?: '/tmp/storage';
    $app->useStoragePath($storage);
}

return $app;
