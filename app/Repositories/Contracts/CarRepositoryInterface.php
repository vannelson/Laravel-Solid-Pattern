<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CarRepositoryInterface
{
    /**
     * List cars with pagination, filters, and sorting.
     *
     * @param array $filters
     * @param array $order
     * @param int $limit
     * @param int $page
     * @param array $includes
     * @return LengthAwarePaginator
     */
    public function listing(array $filters = [], array $order = [], int $limit = 10, int $page = 1, array $includes = []): LengthAwarePaginator;

    /**
     * Append next available window metadata to a collection of cars.
     *
     * @param Collection $cars
     * @return void
     */
    public function enrichWithNextAvailableWindow(Collection $cars): void;
}
