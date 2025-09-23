<?php

namespace App\Services\Contracts;

interface CarServiceInterface
{
    /**
     * List cars with pagination, filters, and sorting.
     *
     * @param array $filters
     * @param array $order
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function getList(array $filters = [], array $order = [], int $limit = 10, int $page = 1): array;

    /**
     * Get details of a car.
     *
     * @param int $id
     * @return array
     */
    public function detail(int $id): array;

    /**
     * Register a new car.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array;

    /**
     * Update an existing car.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a car by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
}
