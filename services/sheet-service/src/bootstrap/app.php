<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Finance\Domains\Auth\Middleware\AuthenticateWithAccessToken;
use Finance\Domains\Core\Exceptions\DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'access-token' => AuthenticateWithAccessToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Исключения предметной области сами знают свой код ответа и несут
        // сообщение, готовое к показу пользователю.
        $exceptions->render(fn (DomainException $exception) => new JsonResponse(
            ['message' => $exception->getMessage()],
            $exception->statusCode(),
        ));
    })->create();
