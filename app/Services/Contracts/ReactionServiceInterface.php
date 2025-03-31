<?php

namespace App\Services\Contracts;

/**
 * Interface ReactionServiceInterface
 */
interface ReactionServiceInterface
{
    /**
     * Add or update a user's reaction for a song.
     *
     * @param int $songId
     * @param int $reactionType
     * @return mixed
     */
    public function addOrUpdateReaction(int $songId, int $reactionType): mixed;

    /**
     * Remove a user's reaction for a song.
     *
     * @param int $songId
     * @return bool
     */
    public function removeReaction(int $songId): bool;

    /**
     * Get a user's reaction for a song.
     *
     * @param int $songId
     * @return mixed
     */
    public function getReaction(int $songId): mixed;
}
