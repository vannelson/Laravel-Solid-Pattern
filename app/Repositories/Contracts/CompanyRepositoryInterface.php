<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CompanyRepositoryInterface
{
    /**
     * Get Users List
     *
     * @param array $filters
     * @param array $order
     * @param int $limit
     * @param int $page
     */
    public function listing(array $filters = [], array $order = [], int $limit = 10, int $page = 1): LengthAwarePaginator;

    /**
     * Clear default flag for user's companies.
     *
     * @param int $userId
     * @param int|null $exceptId
     */
    public function clearDefaultForUser(int $userId, ?int $exceptId = null): void;

    /**
     * Find nearby companies within a radius.
     *
     * @param float $latitude
     * @param float $longitude
     * @param int $radiusMeters
     * @param array $options
     * @return Collection
     */
    public function findNearby(float $latitude, float $longitude, int $radiusMeters, array $options = []): Collection;
}
