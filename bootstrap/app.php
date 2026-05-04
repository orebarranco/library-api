<?php

declare(strict_types=1);

use App\Exceptions\ApiExceptionHandler;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\SetApiVersion;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        $middleware->alias([
            'api.version' => SetApiVersion::class,
            'verified' => EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(
            fn (Throwable $e): JsonResponse => resolve(ApiExceptionHandler::class)->render($e)
        );
    })->create();
