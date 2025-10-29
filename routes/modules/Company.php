<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;

// All company routes require authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::get('companies/nearby', [CompanyController::class, 'nearby'])
        ->name('companies.nearby');
    Route::post('companies/upload-logo', [CompanyController::class, 'uploadLogo'])
        ->name('companies.upload-logo');
    Route::apiResource('companies', CompanyController::class);
});
