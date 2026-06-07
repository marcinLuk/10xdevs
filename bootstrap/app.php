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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // The non-prod /eval/grounding endpoint is called by promptfoo/curl with a
        // shared token, not a browser session, so it carries no CSRF token. Exempt
        // it (the route itself only exists under local|testing).
        $middleware->validateCsrfTokens(except: [
            'eval/grounding',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
