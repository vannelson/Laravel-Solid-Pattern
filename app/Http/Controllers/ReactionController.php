<?php

namespace App\Http\Controllers;

use App\Services\Contracts\ReactionServiceInterface;
use Illuminate\Http\JsonResponse;
use App\Traits\ResponseTrait;

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
            $this->reactionService->addOrUpdateReaction($songId, $reactionType);
            return $this->success('Reaction saved successfully!');
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
            $this->reactionService->removeReaction($songId);
            return $this->success('Reaction removed successfully!');
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
            return $this->success('Reaction retrieved successfully!', $reaction);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve reaction.', 500);
        }
    }
}
