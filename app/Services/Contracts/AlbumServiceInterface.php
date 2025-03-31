<?php

namespace App\Services\Contracts;

interface AlbumServiceInterface
{
     /**
     * Retrieve a paginated list of albums with sorting and filtering.
     *
     * @param array 
     * @param array 
     * @param int 
     * @param int 
     * @return array 
     */
    public function getPaginatedAlbums(array $filters, array $order, int $limit, int $page): array;

    /**
     * Create an album by the user.
     *
     * @param array $data
     * @return mixed
     */
    public function createAlbumByUser(array $data);

    /**
     * Update an album by the user.
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function updateAlbumByUser(int $id, array $data);

    /**
     * Delete an album by the user.
     *
     * @param int $id
     * @return mixed
     */
    public function deleteAlbumByUser(int $id);
}
