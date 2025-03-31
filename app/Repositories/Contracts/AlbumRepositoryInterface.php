<?php

namespace App\Repositories\Contracts;
use Illuminate\Pagination\LengthAwarePaginator;

interface AlbumRepositoryInterface
{

    /**
     * Retrieve a paginated list of albums ordered by song count.
     *
     * @param array 
     * @param array 
     * @param int 
     * @param int 
     * @return LengthAwarePaginator
    */
    public function listAlbums(array $filters = [], array $order = [], int $limit = 10, int $page = 1): LengthAwarePaginator;
}
