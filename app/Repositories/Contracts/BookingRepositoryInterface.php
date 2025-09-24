<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface BookingRepositoryInterface
{
    /**
     * List bookings with filters and pagination.
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
     * Check if a car has overlapping bookings.
     *
     * @param int $carId
     * @param string $startDate
     * @param string $endDate
     * @param int|null $excludeId
     * @return bool
     */
    public function hasConflict(int $carId, string $startDate, string $endDate, int $excludeId = null): bool;
}
