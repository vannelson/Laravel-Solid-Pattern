<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface CarRepositoryInterface
{
    /**
     * List cars with pagination, filters, and sorting.
     *
     * @param array $filters
     * @param array $order
     * @param int $limit
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function listing(array $filters = [], array $order = [], int $limit = 10, int $page = 1): LengthAwarePaginator;
}
