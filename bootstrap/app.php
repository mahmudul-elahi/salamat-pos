<?php

declare(strict_types=1);

use App\Http\Middleware\AuthorizeMiddleware;
use App\Http\Middleware\EmailConfigurationCheckMiddleware;
use App\Http\Middleware\EnsureUserBelongsToTenant;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\ReplaceCdnUrl;
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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'authorize' => AuthorizeMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'tenant.check' => EnsureUserBelongsToTenant::class,
            'check.email.config' => EmailConfigurationCheckMiddleware::class,
        ]);

        $middleware->append(ReplaceCdnUrl::class);
        // Define a middleware group
        $middleware->group('auth.permission', [
            'authorize',
            'permission',
            'tenant.check',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
