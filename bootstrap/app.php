<?php

use App\Modules\Destinations\Http\Middleware\HandleUrlRedirects;
use App\Modules\Identity\Http\Middleware\EnsureActiveUser;
use App\Modules\Identity\Http\Middleware\EnsureCustomerAccount;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/auth.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureActiveUser::class,
            'customer' => EnsureCustomerAccount::class,
        ]);

        $middleware->prepend([
            HandleUrlRedirects::class,
        ]);

        $middleware->prependToPriorityList(
            AuthenticatesRequests::class,
            EnsureActiveUser::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
