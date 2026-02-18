<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('periodization')->group(function () {
        // Periodization endpoints serán agregados aquí
    });
});
