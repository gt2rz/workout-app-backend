<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('exercises')->group(function () {
        // Exercise endpoints serán agregados aquí
    });
});
