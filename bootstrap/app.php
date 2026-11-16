<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', \HotwiredLaravel\TurboLaravel\Http\Middleware\TurboMiddleware::class);

        // The visitor token cookie is an opaque 32-char hex identifier, never a
        // secret. Leaving it encrypted made Laravel write a ~312-char payload
        // into whatsapp_clicks.visitor_token (varchar 255), which MySQL/MariaDB
        // rejects in strict mode with SQLSTATE 22001 — every WhatsApp click
        // 500'd. Excluding it keeps the stored and transmitted value the raw
        // token. See PHASE_4_CLOSURE_AUDIT.md C2.
        $middleware->encryptCookies([
            \App\Services\WhatsAppTrackingService::VISITOR_COOKIE,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
