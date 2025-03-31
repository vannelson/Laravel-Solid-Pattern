<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::post('/register', [UserController::class, 'register']);
Route::put('/users/{id}', [UserController::class, 'update']);
// Require authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/users/{id}', [UserController::class, 'delete']);
});
