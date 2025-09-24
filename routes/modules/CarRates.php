<?php

use App\Http\Controllers\CarRateController;
use Illuminate\Support\Facades\Route;

// All car rate routes require authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('car-rates', CarRateController::class);
});
