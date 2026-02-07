<?php

use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

/**
 * Agrupar por versión es una norma estándar para evitar romper cambios en el futuro.
 */
Route::prefix('v1')->group(function () {

    // Ruta de bienvenida: Provee información básica de la API
    Route::get('/', function () {
        return response()->json([
            'status' => 'success',
            'message' => 'Workout API Service',
            'version' => '1.0.0',
            'docs' => url('/api/v1/docs'), // Enlace a la documentación OpenAPI/Swagger
        ], 200);
    });

    /**
     * Health Check: Los estándares actuales sugieren incluir más que un texto.
     * Se debe indicar el estado de salud del sistema (uptime, base de datos, etc.)
     */
    Route::get('/health', HealthController::class);

    /**
     * Documentación de la API: Ruta para acceder a la documentación generada automáticamente.
     * Se recomienda usar herramientas como Swagger o
     */
    Route::get('/docs', function () {
        return response()->json([
            'message' => 'API documentation is available at /api/v1/docs/swagger or /api/v1/docs/redoc',
        ], 200);
    });

    /**
     * Password Reset Routes
     */
    Route::prefix('auth')->group(function () {
        Route::post('/password/forgot', [PasswordResetController::class, 'forgotPassword']);
        Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);
    });
});
