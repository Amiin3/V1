<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: ['admin/login']);
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // BAR-BAR MODE: Laporkan dan render semua exception tanpa pengecualian
        $exceptions->report(function (\Throwable $e) {
            // Biarkan default reporting jalan
        });
    })->create();
