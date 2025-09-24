<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;

// All company routes require authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('companies', CompanyController::class);
});
