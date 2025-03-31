<?php

namespace App\Repositories;

use App\Models\Reaction;
use App\Repositories\Contracts\ReactionRepositoryInterface;

/**
 * Class ReactionRepository
 *
 */
class ReactionRepository extends BaseRepository implements ReactionRepositoryInterface
{
    /**
     * ReactionRepository constructor.
     *
     * @param Reaction $reaction
     */
    public function __construct(Reaction $reaction)
    {
        parent::__construct($reaction);
    }

    /**
     * Store or update a user's reaction for a song.
     *
     * @param int $userId
     * @param int $songId
     * @param int $reactionType
     * @return Reaction
     */
    public function storeReaction(int $userId, int $songId, int $reactionType): ?Reaction
    {
        $reaction = $this->model->where('user_id', $userId)->where('song_id', $songId)->first();

        if ($reaction && $reaction->reaction_type === $reactionType) {
            $reaction->delete(); 
            return null;
        }
        
        return $this->model->updateOrCreate(
            ['user_id' => $userId, 'song_id' => $songId],
            ['reaction_type' => $reactionType]
        );
    }


    /**
     * Remove a user's reaction from an album.
     *
     * @param int $userId
     * @param int $songId
     * @return bool
     */
    public function removeReaction(int $userId, int $songId): bool
    {
        $reaction = $this->model->where('user_id', $userId)->where('song_id', $songId)->first();
        if ($reaction) {
            return $reaction->delete();
        }
        return false;
    }

    /**
     * Get a user's reaction for an album.
     *
     * @param int $userId
     * @param int $songId
     * @return mixed
     */
    public function getReaction(int $userId, int $songId): mixed
    {
        return $this->model->where('user_id', $userId)->where('song_id', $songId)->first();
    }
}
