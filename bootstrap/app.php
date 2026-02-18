<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\ConditionalResponse;
use App\Http\Middleware\EnsureApiKeyIsValid;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\IdempotencyMiddleware;
use App\Http\Middleware\RequestLogger;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            $featureRoutes = glob(app_path('Features/*/routes.php'));

            Route::prefix('api/v1')->middleware([
                EnsureApiKeyIsValid::class,
                ForceHttps::class,
                SecurityHeaders::class,
                RequestLogger::class,
                IdempotencyMiddleware::class,
                ConditionalResponse::class,
                'throttle:api',
            ])
                ->group(function () use ($featureRoutes) {
                    foreach ($featureRoutes as $routeFile) {
                        require $routeFile;
                    }
                });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Forzar HTTPS en todas las solicitudes (excepto en entorno local)
        $middleware->append(ForceHttps::class);

        // Esto asegura que Laravel confíe en los headers de HTTPS
        // enviados por proxies como Cloudflare o Nginx
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (ApiException $e) {
            return $e->render();
        });

        $exceptions->renderable(function (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 'VALIDATION_ERROR',
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->renderable(function (AuthenticationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'No autenticado.',
                'code' => 'AUTH.UNAUTHENTICATED',
            ], 401);
        });

        $exceptions->renderable(function (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Recurso no encontrado.',
                'code' => 'RESOURCE_NOT_FOUND',
            ], 404);
        });

        $exceptions->renderable(function (ThrottleRequestsException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Demasiadas solicitudes. Intenta más tarde.',
                'code' => 'RATE_LIMITED',
            ], 429);
        });
    })->create();
