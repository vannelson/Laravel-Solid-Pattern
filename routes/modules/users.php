<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

// Public: register a user (apply tighter auth throttle)
Route::post('users', [UserController::class, 'store'])->middleware('throttle:auth');

// Protected: all other user routes require token
Route::middleware('auth:sanctum')->group(function () {
    // Admin-only endpoints
    Route::middleware('admin')->group(function () {
        Route::get('users', [UserController::class, 'index']);
        Route::delete('users/{id}', [UserController::class, 'destroy']);
    });

    // Regular authenticated endpoints
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::put('users/{id}', [UserController::class, 'update']);
    Route::patch('users/{id}', [UserController::class, 'update']);
});
