<?php
namespace App\Services;

use App\Repositories\Contracts\ReactionRepositoryInterface;
use App\Services\Contracts\ReactionServiceInterface;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ReactionResource;

/**
 * Class ReactionService
 *
 * Handles logic for reactions to songs.
 */
class ReactionService implements ReactionServiceInterface
{
    protected ReactionRepositoryInterface $reactionRepository;

    /**
     * ReactionService constructor.
     *
     * @param ReactionRepositoryInterface $reactionRepository
     */
    public function __construct(ReactionRepositoryInterface $reactionRepository)
    {
        $this->reactionRepository = $reactionRepository;
    }

    /**
     * Add or update a user's reaction for a song.
     *
     * @param int $songId
     * @param int $reactionType
     * @return mixed
     */
    public function addOrUpdateReaction(int $songId, int $reactionType): mixed
    {
        $userId = Auth::id();
        return $this->reactionRepository->storeReaction($userId, $songId, $reactionType);
    }

    /**
     * Remove a user's reaction for a song.
     *
     * @param int $songId
     * @return bool
     */
    public function removeReaction(int $songId): bool
    {
        $userId = Auth::id();
        return $this->reactionRepository->removeReaction($userId, $songId);
    }

    /**
     * Get a user's reaction for a song.
     *
     * @param int $songId
     * @return array
     */
    public function getReaction(int $songId): array
    {
        $userId = Auth::id();
        $reaction = $this->reactionRepository->getReaction($userId, $songId);

        return (new ReactionResource($reaction))->response()->getData(true);
    }
}

