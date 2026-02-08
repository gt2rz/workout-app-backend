<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/**
 * Landing page para password reset
 * Intenta abrir la app con deep link, si no funciona muestra instrucciones
 */
Route::get('/password/reset/{token}', function (string $token) {
    $email = request('email');
    $scheme = config('app.scheme', 'workoutapp');
    $deepLink = "{$scheme}://reset-password?token={$token}&email={$email}";
    $apiEndpoint = route('api.password.reset.info', compact('token', 'email'));

    // Generar Expo Development URL si está en modo desarrollo
    $expoDevelopmentUrl = null;
    if (config('app.debug') && env('EXPO_DEV_MODE')) {
        // exp://192.168.x.x:8081/--/reset-password?token=xxx&email=xxx
        $host = request()->getHost();
        $expoDevelopmentUrl = "exp://{$host}:8081/--/reset-password?token={$token}&email={$email}";
    }

    return view('password-reset-redirect', compact('deepLink', 'apiEndpoint', 'token', 'email', 'expoDevelopmentUrl'));
})->name('password.reset');

/**
 * Endpoint de información para desarrolladores
 */
Route::get('/api/password/reset/info', function () {
    return response()->json([
        'message' => 'Para restablecer tu contraseña, usa el endpoint POST /api/v1/auth/password/reset',
        'endpoint' => url('/api/v1/auth/password/reset'),
        'method' => 'POST',
        'body' => [
            'email' => request('email'),
            'token' => request('token'),
            'password' => 'nueva_password',
            'password_confirmation' => 'nueva_password',
        ],
    ]);
})->name('api.password.reset.info');
