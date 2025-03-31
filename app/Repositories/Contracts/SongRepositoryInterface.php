<?php

namespace App\Repositories\Contracts;
use Illuminate\Pagination\LengthAwarePaginator;

interface SongRepositoryInterface
{
    public function listSongs(array $filters = [], array $order = [], int $limit = 10, int $page = 1): LengthAwarePaginator;
}
