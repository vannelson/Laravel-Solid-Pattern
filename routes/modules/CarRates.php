<?php

use App\Http\Controllers\CarRateController;
use Illuminate\Support\Facades\Route;

Route::apiResource('car-rates', CarRateController::class);
