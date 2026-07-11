<?php

use App\Http\Middleware\EnsureIdempotency;
use App\Http\Responses\Envelope;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ThrottleRequestsException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->use([
            HandleCors::class,
        ]);

        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'idempotency' => EnsureIdempotency::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $shouldEnvelope = fn (Request $request): bool => $request->is('api/*');

        $exceptions->render(function (ValidationException $e, Request $request) use ($shouldEnvelope) {
            if ($shouldEnvelope($request)) {
                return Envelope::error($e->getMessage(), $e->errors(), $e->status);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($shouldEnvelope) {
            if ($shouldEnvelope($request)) {
                return Envelope::error('Unauthenticated.', [], 401);
            }
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($shouldEnvelope) {
            if ($shouldEnvelope($request)) {
                return Envelope::error($e->getMessage() ?: 'This action is unauthorized.', [], 403);
            }
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($shouldEnvelope) {
            if ($shouldEnvelope($request)) {
                return Envelope::error('Too many requests.', [], 429)
                    ->withHeaders($e->getHeaders());
            }
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) use ($shouldEnvelope) {
            if ($shouldEnvelope($request)) {
                return Envelope::error('Resource not found.', [], 404);
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) use ($shouldEnvelope) {
            if (! $shouldEnvelope($request)) {
                return null;
            }

            if ($e instanceof HttpExceptionInterface) {
                return Envelope::error($e->getMessage() ?: 'Request failed.', [], $e->getStatusCode())
                    ->withHeaders($e->getHeaders());
            }

            return Envelope::error(
                config('app.debug') ? $e->getMessage() : 'Server error.',
                [],
                500
            );
        });
    })->create();
