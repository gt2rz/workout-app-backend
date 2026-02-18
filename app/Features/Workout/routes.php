<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('workouts')->group(function () {
        // Workout endpoints serán agregados aquí
    });
});
