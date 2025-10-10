<?php

namespace App\Services\Contracts;

interface PaymentServiceInterface
{
    /**
     * Paginate payments associated with a booking.
     */
    public function listByBooking(
        int $bookingId,
        array $filters = [],
        array $order = ['paid_at', 'desc'],
        int $limit = 10,
        int $page = 1
    ): array;

    /**
     * Register a new payment against a booking and return a payload for the API.
     */
    public function register(int $bookingId, array $data): array;
}
