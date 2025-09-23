<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

// RESTful resource routes
Route::apiResource('users', UserController::class);
