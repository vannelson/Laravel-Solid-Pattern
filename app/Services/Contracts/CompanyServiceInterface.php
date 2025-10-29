<?php

namespace App\Services\Contracts;

interface CompanyServiceInterface
{
    /**
     * List companies with pagination, filters, and sorting.
     *
     * @param array $filters
     * @param array $order
     * @param int $limit
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
    */
    public function getList(array $filters = [], array $order = [], int $limit = 10, int $page = 1): array;


    /**
     * 
     * @param int $id
     * @return array
     */
    public function detail($id): array;

    /**
     * Create a new user.
     *
     * @param array $data
     * @return mixed
     */
    public function register(array $data);

    /**
     * Update a user.
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function update(int $id, array $data);

    /**
     * Locate nearby companies around a coordinate.
     *
     * @param array $params
     * @return array
     */
    public function findNearby(array $params): array;

    /**
     * Delete a user.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id);
}
