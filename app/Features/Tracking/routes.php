<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('tracking')->group(function () {
        // Tracking endpoints serán agregados aquí
    });
});
