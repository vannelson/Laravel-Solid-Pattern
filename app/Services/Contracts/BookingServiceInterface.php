<?php

namespace App\Services\Contracts;

interface BookingServiceInterface
{
    /**
     * List bookings with pagination, filters, and sorting.
     *
     * @param array $filters
     * @param array $order
     * @param int $limit
     * @param int $page
     * @param array $includes
     * @return array
     */
    public function getList(array $filters = [], array $order = [], int $limit = 10, int $page = 1, array $includes = []): array;

    /**
     * Show booking detail.
     *
     * @param int $id
     * @return array
     */
    public function detail(int $id): array;

    /**
     * Create a new booking.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array;

    /**
     * Update an existing booking.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a booking by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
}
