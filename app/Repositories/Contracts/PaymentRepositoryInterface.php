<?php

namespace App\Repositories\Contracts;

use App\Models\Payment;
use Illuminate\Pagination\LengthAwarePaginator;

interface PaymentRepositoryInterface extends RepositoryInterface
{
    /**
     * Fetch paginated payments for a booking.
     */
    public function listByBooking(
        int $bookingId,
        array $filters = [],
        array $order = ['paid_at', 'desc'],
        int $limit = 10,
        int $page = 1
    ): LengthAwarePaginator;

    /**
     * Grab the latest payment record for the booking, if any.
     */
    public function latestForBooking(int $bookingId): ?Payment;
}
