<?php

use App\Http\Middleware\EnsureApiKeyIsValid;
use App\Http\Middleware\ForceHttps;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {

            Route::prefix('api/v1')->middleware([EnsureApiKeyIsValid::class, ForceHttps::class, 'throttle:api'])
                ->group(function () {
                    Route::prefix('auth')
                        ->group(base_path('routes/modules/auth.php'));

                    Route::middleware('auth:sanctum')
                        ->group(function () {
                            Route::prefix('profile')
                                ->group(base_path('routes/modules/profile.php'));
                            Route::prefix('home')
                                ->group(base_path('routes/modules/home.php'));
                        });
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
        //
    })->create();
