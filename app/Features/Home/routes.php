<?php

use App\Features\Home\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('home')->group(function () {
        Route::get('/', HomeController::class);
    });
});
