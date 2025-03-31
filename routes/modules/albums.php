<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AlbumController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/albums', [AlbumController::class, 'list'])->name('albums.list');
    Route::post('/albums', [AlbumController::class, 'create'])->name('albums.create');
    Route::put('/albums/{id}', [AlbumController::class, 'update'])->name('albums.update');
    Route::delete('/albums/{id}', [AlbumController::class, 'delete'])->name('albums.delete');
});
