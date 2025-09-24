<?php

namespace App\Services\Contracts;

interface CarRateServiceInterface
{
    /**
     * List car rates with pagination, filters, and sorting.
     *
     * @param array $filters
     * @param array $order
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function getList(array $filters = [], array $order = [], int $limit = 10, int $page = 1): array;

    /**
     * Get details of a car rate by ID.
     *
     * @param int $id
     * @return array
     */
    public function detail($id): array;

    /**
     * Create a new car rate.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array;

    /**
     * Update an existing car rate.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a car rate by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
}
