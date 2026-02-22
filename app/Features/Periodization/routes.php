<?php

use App\Features\Periodization\Controllers\MacrocycleController;
use App\Features\Periodization\Controllers\MesocycleController;
use App\Features\Periodization\Controllers\MicrocycleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('periodization')->group(function () {
        Route::apiResource('macrocycles', MacrocycleController::class);

        Route::apiResource('macrocycles.mesocycles', MesocycleController::class)
            ->scoped();

        Route::apiResource('macrocycles.mesocycles.microcycles', MicrocycleController::class)
            ->scoped();
    });
});
