<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// 👉 import your middlewares here
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\ForceJsonResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {

        /*
        |--------------------------------------------------------------------------
        | Global / Group Middleware
        |--------------------------------------------------------------------------
        */

        // add your JSON middleware to API group (your existing line)
        $middleware->prependToGroup('api', ForceJsonResponse::class);

        /*
        |--------------------------------------------------------------------------
        | Route Middleware Aliases (Laravel 5 Kernel replacement)
        |--------------------------------------------------------------------------
        */

        $middleware->alias([
            'checkrole' => CheckRole::class,
        ]);

    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })

    ->create();
