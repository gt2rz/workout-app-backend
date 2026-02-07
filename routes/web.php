<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/**
 * Ruta temporal para password reset (usado en notificación de email)
 * En producción, redireccionar a tu app móvil o frontend
 */
Route::get('/password/reset/{token}', function (string $token) {
    return response()->json([
        'message' => 'Para restablecer tu contraseña, usa el endpoint POST /api/v1/auth/password/reset',
        'token' => $token,
        'email' => request('email'),
    ]);
})->name('password.reset');
