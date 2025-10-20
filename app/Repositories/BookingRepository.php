<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;

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

        if ($companyId = Arr::get($filters, 'company_id')) {
            $query->where('company_id', $companyId);
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

        if ($monthFilter = Arr::get($filters, 'month')) {
            $reference = $this->resolveMonthFilter($monthFilter, Arr::get($filters, 'year'));

            if ($reference !== null) {
                $startOfMonth = $reference->copy()->startOfMonth()->startOfDay();
                $endOfMonth = $reference->copy()->endOfMonth()->endOfDay();

                $query->where(function ($query) use ($startOfMonth, $endOfMonth) {
                    $query->where('start_date', '<=', $endOfMonth)
                        ->whereRaw('COALESCE(end_date, expected_return_date, start_date) >= ?', [$startOfMonth]);
                });
            }
        }

        if ($weekFilter = Arr::get($filters, 'week')) {
            $range = $this->resolveWeekRange($weekFilter, Arr::get($filters, 'year'));

            if ($range !== null) {
                [$startOfWeek, $endOfWeek] = $range;
                $startOfWeek = $startOfWeek->copy()->startOfDay();
                $endOfWeek = $endOfWeek->copy()->endOfDay();

                $query->where(function ($query) use ($startOfWeek, $endOfWeek) {
                    $query->where('start_date', '<=', $endOfWeek)
                        ->whereRaw('COALESCE(end_date, expected_return_date, start_date) >= ?', [$startOfWeek]);
                });
            }
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

    /**
     * Resolve a month filter value into a Carbon instance (first day of the month).
     */
    protected function resolveMonthFilter($monthFilter, $yearHint = null): ?Carbon
    {
        try {
            if (is_numeric($monthFilter) && $yearHint !== null) {
                return Carbon::createFromDate((int) $yearHint, (int) $monthFilter, 1);
            }

            if (is_string($monthFilter) && Str::contains($monthFilter, '-')) {
                return Carbon::parse($monthFilter);
            }

            if ($yearHint !== null) {
                return Carbon::createFromDate((int) $yearHint, (int) $monthFilter, 1);
            }

            if (is_numeric($monthFilter)) {
                return Carbon::createFromDate((int) Carbon::now()->year, (int) $monthFilter, 1);
            }

            return Carbon::parse($monthFilter);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Resolve a week filter into a [start, end] Carbon range.
     */
    protected function resolveWeekRange($weekFilter, $yearHint = null): ?array
    {
        try {
            if (is_string($weekFilter) && preg_match('/^\d{4}-W\d{1,2}$/i', $weekFilter)) {
                [$year, $week] = sscanf(strtoupper($weekFilter), '%d-W%d');
                $reference = Carbon::now()->setISODate((int) $year, (int) $week);
            } elseif (is_string($weekFilter) && Str::contains($weekFilter, '-')) {
                $reference = Carbon::parse($weekFilter);
            } elseif (is_numeric($weekFilter)) {
                $year = $yearHint !== null ? (int) $yearHint : (int) Carbon::now()->year;
                $reference = Carbon::now()->setISODate($year, (int) $weekFilter);
            } else {
                return null;
            }

            $start = $reference->copy()->startOfWeek();
            $end = $reference->copy()->endOfWeek();

            return [$start, $end];
        } catch (\Throwable $e) {
            return null;
        }
    }
}
