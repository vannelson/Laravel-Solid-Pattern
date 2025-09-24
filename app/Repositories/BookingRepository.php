<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class BookingRepository extends BaseRepository implements BookingRepositoryInterface
{
    public function __construct(Booking $booking)
    {
        parent::__construct($booking);
    }

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
    public function listing(array $filters = [], array $order = [], int $limit = 10, int $page = 1, array $includes = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (!empty($includes)) {
            $query->with($includes);
        }

        if ($carId = Arr::get($filters, 'car_id')) {
            $query->where('car_id', $carId);
        }
        if ($borrowerId = Arr::get($filters, 'borrower_id')) {
            $query->where('borrower_id', $borrowerId);
        }
        if ($tenantId = Arr::get($filters, 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }
        if ($status = Arr::get($filters, 'status')) {
            $query->where('status', $status);
        }
        if ($paymentStatus = Arr::get($filters, 'payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }
        if ($destination = Arr::get($filters, 'destination')) {
            $query->where('destination', 'LIKE', "%$destination%");
        }
        if (Arr::has($filters, 'start_date') && Arr::has($filters, 'end_date')) {
            $query->whereBetween('start_date', [$filters['start_date'], $filters['end_date']]);
        }

        [$orderBy, $dir] = !empty($order) ? $order : ['id', 'desc'];
        $query->orderBy($orderBy, $dir);

        return $query->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Check if a car has overlapping bookings.
     *
     * @param int $carId
     * @param string $startDate
     * @param string $endDate
     * @param int|null $excludeId
     * @return bool
     */
    public function hasConflict(int $carId, string $startDate, string $endDate, int $excludeId = null): bool
    {
        $query = $this->model
            ->where('car_id', $carId)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('start_date', '<=', $endDate)
                    ->where('end_date', '>=', $startDate);
            })
            ->whereNotIn('status', ['Cancelled', 'Completed']);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
