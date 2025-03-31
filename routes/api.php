<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Load modular routes
require __DIR__ . '/modules/auth.php';
require __DIR__ . '/modules/users.php';
require __DIR__ . '/modules/albums.php';
require __DIR__ . '/modules/reaction.php';
require __DIR__ . '/modules/songs.php';

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
