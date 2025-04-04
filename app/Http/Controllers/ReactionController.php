<?php

namespace App\Http\Controllers;

use App\Services\Contracts\ReactionServiceInterface;
use Illuminate\Http\JsonResponse;
use App\Traits\ResponseTrait;
use App\Events\ReactionUpdated; // Import the broadcastable event

/**
 * Class ReactionController
 *
 * Handles reaction-related requests.
 */
class ReactionController extends Controller
{
    use ResponseTrait;

    protected ReactionServiceInterface $reactionService;

    public function __construct(ReactionServiceInterface $reactionService)
    {
        $this->reactionService = $reactionService;
    }

    /**
     * Add or update a user's reaction for a song.
     *
     * @param int $songId
     * @param int $reactionType
     * @return JsonResponse
     */
    public function addOrUpdateReaction(int $songId, int $reactionType): JsonResponse
    {
        try {
            $reaction = $this->reactionService->addOrUpdateReaction($songId, $reactionType);

            broadcast(new ReactionUpdated($reaction));

            return $this->success('Reaction saved successfully!', $reaction->toArray());
        } catch (\Exception $e) {
            return $this->error('Failed to save reaction.', 500);
        }
    }

    /**
     * Remove a user's reaction for a song.
     *
     * @param int $songId
     * @return JsonResponse
     */
    public function removeReaction(int $songId): JsonResponse
    {
        try {
            $reaction = $this->reactionService->removeReaction($songId);

            broadcast(new ReactionUpdated($reaction));

            return $this->success('Reaction removed successfully!', $reaction->toArray());
        } catch (\Exception $e) {
            return $this->error('Failed to remove reaction.', 500);
        }
    }

    /**
     * Get a user's reaction for a song.
     *
     * @param int $songId
     * @return JsonResponse
     */
    public function getReaction(int $songId): JsonResponse
    {
        try {
            $reaction = $this->reactionService->getReaction($songId);
            // If reaction exists, return its array representation; otherwise, return null.
            return $this->success('Reaction retrieved successfully!', $reaction ? $reaction->toArray() : null);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve reaction.', 500);
        }
    }
}
