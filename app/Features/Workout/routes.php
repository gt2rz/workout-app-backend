<?php

use App\Features\Workout\Controllers\WorkoutSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('workouts')->group(function () {
        Route::apiResource('sessions', WorkoutSessionController::class);
    });
});
