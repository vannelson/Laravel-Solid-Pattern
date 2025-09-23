<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;

Route::apiResource('companies', CompanyController::class);
