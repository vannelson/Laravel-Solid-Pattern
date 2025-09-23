<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Load modular routes
require __DIR__ . '/modules/Auth.php';
require __DIR__ . '/modules/Users.php';
require __DIR__ . '/modules/Albums.php';
require __DIR__ . '/modules/Reaction.php';
require __DIR__ . '/modules/Songs.php';
require __DIR__ . '/modules/Company.php';

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
