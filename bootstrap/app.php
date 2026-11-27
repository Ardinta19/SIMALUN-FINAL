<?php

use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\SanitizeInput;
use App\Http\Middleware\SecurityHeaders;
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
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        // Apply input sanitization to all web requests
        $middleware->web(append: [
            SanitizeInput::class,
            SecurityHeaders::class,
            EnsureActiveAccount::class,
        ]);

        // CATATAN: proxy tepercaya TIDAK diatur di sini. Closure ini dijalankan
        // saat kernel diselesaikan, yaitu SEBELUM bootstrapper LoadConfiguration,
        // sehingga `config()` belum tersedia — memanggilnya menghasilkan
        // "Target class [config] does not exist" pada setiap perintah artisan.
        // Pengaturannya ada di AppServiceProvider::boot(), setara dengan
        // `$middleware->trustProxies(at: ...)`.
    })
        //
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
