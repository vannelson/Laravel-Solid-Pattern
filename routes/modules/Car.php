<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CarController;

// All car routes require authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('cars', CarController::class);
});
