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
        // API pura: não existe rota `login` para redirecionar. Sem isto, um pedido SEM `Accept: application/json`
        // (Insomnia, curl) faz o middleware `auth` chamar route('login') e responder 500 em vez de 401.
        $middleware->redirectGuestsTo(fn (Request $request) => null);
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
