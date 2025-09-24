<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;

// All booking routes require authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('bookings', BookingController::class);
});
