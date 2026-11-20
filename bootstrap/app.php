<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'authorize' => \App\Http\Middleware\AuthorizeMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'install' => \App\Http\Middleware\InstallMiddleware::class,
            'check.email.config' => \App\Http\Middleware\EmailConfigurationCheckMiddleware::class,
        ]);

        $middleware->append(\App\Http\Middleware\ReplaceCdnUrl::class);
        // Define a middleware group
        $middleware->group('auth.permission', [
            'authorize',
            'permission',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
