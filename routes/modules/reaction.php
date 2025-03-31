<?php
use App\Http\Controllers\ReactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('songs/{songId}/reactions/{reactionType}', [ReactionController::class, 'addOrUpdateReaction']);
    Route::delete('songs/{songId}/reactions', [ReactionController::class, 'removeReaction']);
    Route::get('songs/{songId}/reactions', [ReactionController::class, 'getReaction']);
});
