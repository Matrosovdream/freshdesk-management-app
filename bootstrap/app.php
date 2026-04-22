<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api/v1.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('rest')
                ->prefix('rest/v1')
                ->group(base_path('routes/rest/v1.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        $middleware->alias([
            'role'              => \App\Http\Middleware\EnsureRole::class,
            'right'             => \App\Http\Middleware\EnsureRight::class,
            'manager.scope'     => \App\Http\Middleware\InjectManagerScope::class,
            'webhook.signature' => \App\Http\Middleware\VerifyWebhookSignature::class,
        ]);

        $middleware->group('rest', [
            \App\Http\Middleware\VerifyWebhookSignature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
