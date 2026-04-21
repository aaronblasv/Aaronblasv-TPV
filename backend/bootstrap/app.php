<?php

use App\Shared\Domain\Exception\BusinessRuleViolationException;
use App\Shared\Domain\Exception\ConcurrencyException;
use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\Exception\UnauthorizedException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);
        $middleware->alias([
            'backoffice' => \App\Http\Middleware\RequireBackofficeRole::class,
            'require.role' => \App\Shared\Infrastructure\Http\Middleware\RequireRoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundException $e): JsonResponse {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        });

        $exceptions->render(function (BusinessRuleViolationException $e): JsonResponse {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        });

        $exceptions->render(function (ConcurrencyException $e): JsonResponse {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        });

        $exceptions->render(function (UnauthorizedException $e): JsonResponse {
            return new JsonResponse(['message' => $e->getMessage()], 401);
        });
    })->create();
