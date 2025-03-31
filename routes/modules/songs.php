<?php

use App\Http\Controllers\SongController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/songs', [SongController::class, 'list'])->name('songs.list');
    Route::post('/songs', [SongController::class, 'create'])->name('songs.create');
    Route::put('/songs/{id}', [SongController::class, 'update'])->name('songs.update');
    Route::delete('/songs/{id}', [SongController::class, 'delete'])->name('songs.delete');
});
