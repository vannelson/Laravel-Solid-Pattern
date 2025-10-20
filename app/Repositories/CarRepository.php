<?php

namespace App\Repositories;

use App\Models\Car;
use App\Repositories\Contracts\CarRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class CarRepository extends BaseRepository implements CarRepositoryInterface
{
    /**
     * CarRepository constructor.
     *
     * @param Car $car
     */
    public function __construct(Car $car)
    {
        parent::__construct($car);
    }

    /**
     * List cars with pagination, filters, and sorting.
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

        $requestedIncludes = Arr::wrap($includes);
        $relationshipIncludes = [];
        $includeNextWindow = false;

        foreach ($requestedIncludes as $includeItem) {
            if ($includeItem === 'next_available_window') {
                $includeNextWindow = true;
                continue;
            }

            $relationshipIncludes[] = $includeItem;
        }

        if (!empty($relationshipIncludes)) {
            $query->with($relationshipIncludes);
        }

        // Primary filters (AND): company, make, availability, transmission, fuel type
        if ($companyId = Arr::get($filters, 'company_id')) {
            $query->where('company_id', $companyId);
        }
        if ($make = Arr::get($filters, 'info_make')) {
            $query->where('info_make', 'LIKE', "%$make%");
        }
        if ($availability = Arr::get($filters, 'info_availabilityStatus')) {
            $query->where('info_availabilityStatus', $availability);
        }
        if ($transmission = Arr::get($filters, 'spcs_transmission')) {
            $query->where('spcs_transmission', $transmission);
        }
        if ($fuelType = Arr::get($filters, 'spcs_fuelType')) {
            $query->where('spcs_fuelType', $fuelType);
        }

        // Build OR group from any other provided info_* or spcs_* filter keys
        $primaryKeys = ['info_make', 'info_availabilityStatus', 'spcs_transmission', 'spcs_fuelType'];
        $orFields = [];
        foreach ((array) $filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if ((strpos($key, 'info_') === 0 || strpos($key, 'spcs_') === 0) && !in_array($key, $primaryKeys, true)) {
                $orFields[$key] = $value;
            }
        }

        // Generic keyword search across info_* and spcs_*
        $keyword = Arr::get($filters, 'search') ?: Arr::get($filters, 'q');

        if (!empty($orFields) || $keyword) {
            $query->where(function ($q) use ($orFields, $keyword) {
                foreach ($orFields as $col => $val) {
                    $q->orWhere($col, 'LIKE', "%{$val}%");
                }
                if ($keyword) {
                    $kw = "%{$keyword}%";
                    $q->orWhere('info_make', 'LIKE', $kw)
                      ->orWhere('info_model', 'LIKE', $kw)
                      ->orWhere('info_year', 'LIKE', $kw)
                      ->orWhere('info_age', 'LIKE', $kw)
                      ->orWhere('info_carType', 'LIKE', $kw)
                      ->orWhere('info_plateNumber', 'LIKE', $kw)
                      ->orWhere('info_vin', 'LIKE', $kw)
                      ->orWhere('info_availabilityStatus', 'LIKE', $kw)
                      ->orWhere('info_location', 'LIKE', $kw)
                      ->orWhere('info_mileage', 'LIKE', $kw)
                      ->orWhere('spcs_seats', 'LIKE', $kw)
                      ->orWhere('spcs_largeBags', 'LIKE', $kw)
                      ->orWhere('spcs_smallBags', 'LIKE', $kw)
                      ->orWhere('spcs_engineSize', 'LIKE', $kw)
                      ->orWhere('spcs_transmission', 'LIKE', $kw)
                      ->orWhere('spcs_fuelType', 'LIKE', $kw)
                      ->orWhere('spcs_fuelEfficiency', 'LIKE', $kw);
                }
            });
        }

        $statusFilter = strtolower((string) Arr::get($filters, 'status', ''));
        $statusFilter = in_array($statusFilter, ['available', 'unavailable'], true) ? $statusFilter : '';

        if (Arr::has($filters, 'start_date') && Arr::has($filters, 'end_date')) {
            try {
                $startDate = Carbon::parse($filters['start_date'])->startOfDay();
                $endDate = Carbon::parse($filters['end_date'])->endOfDay();

                if ($endDate->greaterThanOrEqualTo($startDate)) {
                    $start = $startDate->toDateTimeString();
                    $end = $endDate->toDateTimeString();

                    $conflictConstraint = function ($bookingQuery) use ($start, $end) {
                        $bookingQuery
                            ->where(function ($statusScope) {
                                $statusScope
                                    ->whereNull('status')
                                    ->orWhereRaw('LOWER(TRIM(status)) NOT IN (?, ?)', ['cancelled', 'completed']);
                            })
                            ->where(function ($conflict) use ($start, $end) {
                                $conflict
                                    ->where('start_date', '<=', $end)
                                    ->whereRaw('COALESCE(end_date, expected_return_date, start_date) >= ?', [$start]);
                            });
                    };
                    if ($statusFilter === 'unavailable') {
                        $query->whereHas('bookings', $conflictConstraint);
                    } else {
                        $query->whereDoesntHave('bookings', $conflictConstraint);
                    }
                }
            } catch (\Throwable $e) {
                // Ignore invalid date filters
            }
        }

        // Ordering
        [$orderBy, $dir] = !empty($order) ? $order : ['id', 'desc'];
        $query->orderBy($orderBy, $dir);

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        if ($includeNextWindow) {
            $this->enrichWithNextAvailableWindow($paginator->getCollection());
        }

        return $paginator;
    }

    public function enrichWithNextAvailableWindow(Collection $cars): void
    {
        if ($cars->isEmpty()) {
            return;
        }

        $cars->loadMissing(['bookings' => function ($query) {
            $query->select('id', 'car_id', 'start_date', 'end_date', 'expected_return_date', 'actual_return_date', 'status')
                ->orderBy('start_date');
        }]);

        foreach ($cars as $car) {
            $car->setAttribute('next_available_window', $this->resolveNextAvailableWindow($car));
        }
    }

    protected function resolveNextAvailableWindow(Car $car): ?array
    {
        $today = Carbon::today();

        $intervals = $car->bookings
            ->filter(function ($booking) {
                $status = strtolower(trim((string) $booking->status));
                return $status !== 'cancelled';
            })
            ->map(function ($booking) {
                $start = $booking->start_date ? Carbon::parse($booking->start_date)->startOfDay() : null;
                $endReference = $booking->actual_return_date
                    ?? $booking->end_date
                    ?? $booking->expected_return_date
                    ?? $booking->start_date;

                if ($start === null || $endReference === null) {
                    return null;
                }

                $end = Carbon::parse($endReference)->endOfDay();

                return compact('start', 'end');
            })
            ->filter()
            ->sortBy('start')
            ->values();

        $pointer = $today->copy();
        $windowStart = null;
        $windowEnd = null;

        foreach ($intervals as $interval) {
            $start = $interval['start'];
            $end = $interval['end'];

            if ($end->lt($pointer)) {
                continue;
            }

            if ($start->gt($pointer)) {
                $windowStart = $pointer->copy();
                $windowEnd = $start->copy()->subDay()->endOfDay();

                if ($windowEnd->lt($windowStart)) {
                    $windowEnd = $windowStart->copy();
                }

                break;
            }

            $pointer = $end->copy()->addDay()->startOfDay();
        }

        if ($windowStart === null) {
            $windowStart = $pointer->copy();
            $windowEnd = null;
        }

        return [
            'start_date' => $windowStart->toDateString(),
            'end_date' => $windowEnd ? $windowEnd->toDateString() : null,
            'label' => $this->formatWindowLabel($windowStart, $windowEnd),
        ];
    }

    protected function formatWindowLabel(Carbon $start, ?Carbon $end): string
    {
        if ($end === null) {
            return $start->format('M j, Y') . ' onward';
        }

        if ($start->isSameMonth($end) && $start->isSameYear($end)) {
            return $start->format('M j') . ' - ' . $end->format('j, Y');
        }

        if ($start->isSameYear($end)) {
            return $start->format('M j') . ' - ' . $end->format('M j, Y');
        }

        return $start->format('M j, Y') . ' - ' . $end->format('M j, Y');
    }

    /**
     * Override update to leverage Eloquent casting/mutators/events.
     * This ensures JSON columns like displayImages/features are saved correctly
     * when arrays are provided, and that attribute casts are applied.
     */
    public function update(int $id, array $data): int
    {
        $model = $this->model->findOrFail($id);
        $model->fill($data);
        $saved = $model->save();
        return $saved ? 1 : 0;
    }
}
