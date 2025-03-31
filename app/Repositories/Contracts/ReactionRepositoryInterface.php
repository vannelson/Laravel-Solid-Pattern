<?php

namespace App\Repositories\Contracts;

/**
 * Interface ReactionRepositoryInterface
 */
interface ReactionRepositoryInterface
{
    /**
     * Store or update a user's reaction for a song.
     *
     * @param int $userId
     * @param int $songId
     * @param int $reactionType
     * @return mixed
     */
    public function storeReaction(int $userId, int $songId, int $reactionType): mixed;

    /**
     * Remove a user's reaction for a song.
     *
     * @param int $userId
     * @param int $songId
     * @return bool
     */
    public function removeReaction(int $userId, int $songId): bool;

    /**
     * Get a user's reaction for a song.
     *
     * @param int $userId
     * @param int $songId
     * @return mixed
     */
    public function getReaction(int $userId, int $songId): mixed;
}
