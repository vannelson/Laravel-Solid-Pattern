<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\SongController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\CarRateController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TenantDashboardController;
use App\Http\Controllers\TenantReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Default Laravel user check
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Users
|--------------------------------------------------------------------------
*/
// Public: register a user
Route::post('users', [UserController::class, 'store'])->middleware('throttle:auth');

// Protected
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

/*
|--------------------------------------------------------------------------
| Albums
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/albums', [AlbumController::class, 'list'])->name('albums.list');
    Route::post('/albums', [AlbumController::class, 'create'])->name('albums.create');
    Route::put('/albums/{id}', [AlbumController::class, 'update'])->name('albums.update');
    Route::delete('/albums/{id}', [AlbumController::class, 'delete'])->name('albums.delete');
});

/*
|--------------------------------------------------------------------------
| Songs
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/songs', [SongController::class, 'list'])->name('songs.list');
    Route::post('/songs', [SongController::class, 'create'])->name('songs.create');
    Route::put('/songs/{id}', [SongController::class, 'update'])->name('songs.update');
    Route::delete('/songs/{id}', [SongController::class, 'delete'])->name('songs.delete');
});

/*
|--------------------------------------------------------------------------
| Reactions
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('songs/{songId}/reactions/{reactionType}', [ReactionController::class, 'addOrUpdateReaction']);
    Route::delete('songs/{songId}/reactions', [ReactionController::class, 'removeReaction']);
    Route::get('songs/{songId}/reactions', [ReactionController::class, 'getReaction']);
});

/*
|--------------------------------------------------------------------------
| Companies
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('companies/upload-logo', [CompanyController::class, 'uploadLogo'])
        ->name('companies.upload-logo');
    Route::apiResource('companies', CompanyController::class);
});

/*
|--------------------------------------------------------------------------
| Cars
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('cars', CarController::class);
    // Upload car images and return public URL
    Route::post('cars/upload', [CarController::class, 'upload'])
        ->name('cars.upload');
});

/*
|--------------------------------------------------------------------------
| Car Rates
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('car-rates', CarRateController::class);
});

/*
|--------------------------------------------------------------------------
| Bookings
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('bookings', BookingController::class);
    Route::get('bookings/{booking}/payments', [PaymentController::class, 'index']);
    Route::post('bookings/{booking}/payments', [PaymentController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Tenant Dashboard
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('tenant/dashboard/summary', [TenantDashboardController::class, 'summary']);
    Route::get('tenant/dashboard/fleet-utilization', [TenantDashboardController::class, 'fleetUtilization']);
    Route::get('tenant/dashboard/highlights', [TenantReportController::class, 'highlights']);
    Route::get('tenant/dashboard/revenue-by-class', [TenantReportController::class, 'revenueByClass']);
    Route::get('tenant/dashboard/monthly-sales', [TenantReportController::class, 'monthlySales']);
    Route::get('tenant/dashboard/utilization', [TenantReportController::class, 'utilization']);
    Route::get('tenant/dashboard/upcoming-bookings', [TenantReportController::class, 'upcomingBookings']);
    Route::get('tenant/dashboard/top-performers', [TenantReportController::class, 'topPerformers']);
});
