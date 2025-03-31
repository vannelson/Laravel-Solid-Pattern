<?php

namespace App\Services\Contracts;

interface SongServiceInterface
{

    /**
     * Retrieve a paginated list of songs 
     *
     * @param array $filters 
     * @param array $order 
     * @param int $limit 
     * @param int $page
     * @return array 
     */
    public function getPaginatedSongs(array $filters, array $order, int $limit, int $page): array;

    /**
     * Create a song.
     *
     * @param array $data
     * @return array
     */
    public function createSong(array $data): array;

    /**
     * Update a song.
     *
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updateSong(int $id, array $data): array;

    /**
     * Delete a song.
     *
     * @param int $id
     * @return bool
     */
    public function deleteSong(int $id): bool;
}
