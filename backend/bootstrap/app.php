<?php

use App\Shared\Http\Middleware\CabecalhosDeSeguranca;
use App\Shared\Support\ProblemDetails;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(append: [CabecalhosDeSeguranca::class]);
        $middleware->throttleApi('api');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn (Request $r) => $r->is('api/*') || $r->expectsJson());
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                return ProblemDetails::from($e, $request);
            }
        });
    })
    ->create();
