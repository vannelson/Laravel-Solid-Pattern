<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Apply tighter rate limit to login to reduce brute force attempts
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
