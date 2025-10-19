<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Car;
use App\Models\Company;
use App\Models\DashboardEvent;
use App\Models\DailyFleetMetric;
use App\Services\Contracts\ReportServiceInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class ReportService implements ReportServiceInterface
{
    protected const DEFAULT_CURRENCY = 'PHP';

    /**
     * Cache of tenant-specific car IDs.
     *
     * @var array<int,array<int>>
     */
    protected array $carIdsCache = [];

    /**
     * Cache of all car IDs.
     *
     * @var array<int>|null
     */
    protected ?array $allCarIdsCache = null;

    public function getKpis(int $year, ?int $tenantId = null): array
    {
        $today = Carbon::now();
        $startOfYear = Carbon::create($year, 1, 1)->startOfDay();
        $endOfYear = Carbon::create($year, 12, 31)->endOfDay();
        $prevStartOfYear = $startOfYear->copy()->subYear();
        $prevEndOfYear = $endOfYear->copy()->subYear();

        $baseQuery = $this->applyTenantFilterToBookings(
            Booking::query()->whereBetween('start_date', [$startOfYear, $endOfYear]),
            $tenantId
        );

        $annualRevenue = (float) (clone $baseQuery)->sum('total_amount');
        $annualBookings = (clone $baseQuery)->count();

        $prevBaseQuery = $this->applyTenantFilterToBookings(
            Booking::query()->whereBetween('start_date', [$prevStartOfYear, $prevEndOfYear]),
            $tenantId
        );

        $prevRevenue = (float) (clone $prevBaseQuery)->sum('total_amount');
        $prevBookings = (clone $prevBaseQuery)->count();

        $avgCurrent = $annualBookings > 0 ? $annualRevenue / $annualBookings : 0.0;
        $avgPrevious = $prevBookings > 0 ? $prevRevenue / $prevBookings : 0.0;

        $ytdEnd = $year === $today->year
            ? $today->copy()->endOfDay()
            : $endOfYear->copy();

        $ytdQuery = $this->applyTenantFilterToBookings(
            Booking::query()->whereBetween('start_date', [$startOfYear, $ytdEnd]),
            $tenantId
        );
        $bookingsYtd = (clone $ytdQuery)->count();

        $prevYtdEnd = $ytdEnd->copy()->subYear();
        $prevYtdQuery = $this->applyTenantFilterToBookings(
            Booking::query()->whereBetween('start_date', [$prevStartOfYear, $prevYtdEnd]),
            $tenantId
        );
        $bookingsPrevYtd = (clone $prevYtdQuery)->count();

        $currentFleet = $this->computeLiveFleetFigures($today, $tenantId);
        $previousFleet = $this->computeLiveFleetFigures($today->copy()->subDays(7), $tenantId);

        return [
            'annualRevenue' => [
                'value' => round($annualRevenue, 2),
                'currency' => self::DEFAULT_CURRENCY,
                'deltaPctYoY' => $this->calculatePercentDelta($annualRevenue, $prevRevenue),
            ],
            'bookingsYtd' => [
                'count' => $bookingsYtd,
                'deltaPctYoY' => $this->calculatePercentDelta((float) $bookingsYtd, (float) $bookingsPrevYtd),
            ],
            'avgBookingValue' => [
                'value' => round($avgCurrent, 2),
                'previous' => round($avgPrevious, 2),
                'deltaPctYoY' => $this->calculatePercentDelta($avgCurrent, $avgPrevious),
                'currency' => self::DEFAULT_CURRENCY,
            ],
            'fleetUtilization' => [
                'activeUnits' => $currentFleet['active_units'],
                'totalUnits' => $currentFleet['total_units'],
                'utilizationPct' => round($currentFleet['utilization_pct'], 1),
                'deltaPctWoW' => $this->calculatePointDelta(
                    $currentFleet['utilization_pct'],
                    $previousFleet['utilization_pct']
                ),
                'returnsDueToday' => $currentFleet['returns_due_today'],
            ],
        ];
    }

    public function getRevenueTrend(int $year, ?int $tenantId = null, ?int $comparisonYear = null): array
    {
        $series = $this->buildMonthlySeries($year, $tenantId);

        $response = [
            'year' => $year,
            'series' => $series,
        ];

        $compareYear = $comparisonYear ?? ($year - 1);
        if ($compareYear > 0) {
            $response['comparison'] = [
                'year' => $compareYear,
                'series' => $this->buildMonthlySeries($compareYear, $tenantId),
            ];
        }

        return $response;
    }

    public function getRevenueByClass(int $year, ?int $tenantId = null): array
    {
        $query = Booking::query()
            ->selectRaw('COALESCE(cars.info_carType, "Unclassified") as class_name')
            ->selectRaw('SUM(bookings.total_amount) as total_revenue')
            ->join('cars', 'cars.id', '=', 'bookings.car_id')
            ->whereYear('bookings.start_date', $year)
            ->groupBy('class_name')
            ->orderByDesc('total_revenue');

        $query = $this->applyTenantFilterToBookings($query, $tenantId);

        $rows = $query->get();
        $totalRevenue = (float) $rows->sum('total_revenue');

        $classes = $rows->map(function ($row) use ($totalRevenue) {
            $revenue = (float) $row->total_revenue;
            $share = $totalRevenue > 0 ? ($revenue / $totalRevenue) * 100 : 0;

            return [
                'className' => $row->class_name,
                'revenue' => round($revenue, 2),
                'sharePct' => round($share, 1),
            ];
        })->values()->all();

        return [
            'totalRevenue' => round($totalRevenue, 2),
            'classes' => $classes,
        ];
    }

    public function getTopVehicles(int $year, ?int $tenantId = null, int $limit = 4): array
    {
        $periodStart = Carbon::create($year, 1, 1)->startOfDay();
        $periodEnd = Carbon::create($year, 12, 31)->endOfDay();

        $bookings = $this->applyTenantFilterToBookings(
            Booking::query()
                ->whereBetween('start_date', [$periodStart, $periodEnd])
                ->select(['id', 'car_id', 'start_date', 'end_date', 'total_amount']),
            $tenantId
        )->get();

        if ($bookings->isEmpty()) {
            return ['items' => []];
        }

        $grouped = $bookings->groupBy('car_id')->map(function ($items, $carId) {
            $totalRevenue = $items->sum('total_amount');
            $bookingCount = $items->count();
            $rentalDays = $items->sum(function (Booking $booking) {
                $start = Carbon::parse($booking->start_date)->startOfDay();
                $end = Carbon::parse($booking->end_date)->endOfDay();

                return max(1, $start->diffInDays($end) + 1);
            });

            return [
                'car_id' => $carId,
                'total_revenue' => $totalRevenue,
                'booking_count' => $bookingCount,
                'rental_days' => $rentalDays,
            ];
        });

        $carIds = $grouped->keys()->filter()->values();
        $cars = Car::query()
            ->whereIn('id', $carIds)
            ->get()
            ->keyBy('id');

        $totalDaysInPeriod = max(1, $periodStart->diffInDays($periodEnd) + 1);

        $items = $grouped->map(function (array $row) use ($cars, $totalDaysInPeriod) {
            $car = $cars->get($row['car_id']);
            if ($car === null) {
                return null;
            }

            $displayName = trim(implode(' ', array_filter([$car->info_make, $car->info_model])));

            $occupancy = $totalDaysInPeriod > 0
                ? min(100, ((float) $row['rental_days'] / $totalDaysInPeriod) * 100)
                : 0;

            return [
                'vehicleId' => $car->id,
                'displayName' => $displayName !== '' ? $displayName : sprintf('Vehicle #%d', $car->id),
                'plateNumber' => $car->info_plateNumber,
                'className' => $car->info_carType ?? 'Unclassified',
                'totalRevenue' => round((float) $row['total_revenue'], 2),
                'occupancyPct' => round($occupancy, 1),
            ];
        })->filter()->values()
        ->sortByDesc(fn ($item) => [$item['totalRevenue'], $item['occupancyPct']])
        ->values()
        ->take($limit)
        ->values();

        $ranked = $items->map(function (array $item, int $index) {
            $item['rank'] = $index + 1;

            return $item;
        })->values()->all();

        return ['items' => $ranked];
    }

    public function getUpcomingBookings(string $range, ?int $tenantId = null): array
    {
        [$start, $end] = $this->resolveRangeWindow($range);

        $query = Booking::query()
            ->with([
                'car:id,info_make,info_model,info_plateNumber,info_carType',
                'borrower:id,first_name,middle_name,last_name',
            ])
            ->whereBetween('start_date', [$start, $end])
            ->whereNotIn('status', ['Cancelled'])
            ->orderBy('start_date');

        $query = $this->applyTenantFilterToBookings($query, $tenantId);

        $bookings = $query->limit(25)->get();

        $items = $bookings->map(function (Booking $booking) {
            $car = $booking->car;
            $guest = trim(implode(' ', array_filter([
                $booking->renter_first_name,
                $booking->renter_middle_name,
                $booking->renter_last_name,
            ])));

            if ($guest === '' && $booking->relationLoaded('borrower') && $booking->borrower) {
                $guest = $booking->borrower->name;
            }

            $pickupAt = $booking->start_date
                ? Carbon::parse($booking->start_date)->toIso8601String()
                : null;

            return [
                'bookingId' => $booking->id,
                'bookingCode' => $booking->booking_code,
                'guestName' => $guest,
                'vehicle' => $car ? [
                    'id' => $car->id,
                    'displayName' => trim(implode(' ', array_filter([$car->info_make, $car->info_model]))),
                    'className' => $car->info_carType,
                ] : null,
                'pickupAt' => $pickupAt,
                'pickupLocation' => $booking->pickup_location ?? $booking->destination,
                'status' => $booking->status,
                'estimatedValue' => round((float) $booking->total_amount, 2),
            ];
        })->values()->all();

        return ['items' => $items];
    }

    public function getActivityFeed(int $limit = 5, ?int $tenantId = null): array
    {
        $query = DashboardEvent::query()
            ->when($tenantId !== null, fn (Builder $builder) => $builder->where('tenant_id', $tenantId))
            ->orderByDesc('occurred_at')
            ->limit($limit);

        $items = $query->get()->map(function (DashboardEvent $event) {
            return [
                'id' => $event->id,
                'occurredAt' => optional($event->occurred_at)->toIso8601String(),
                'title' => $event->title,
                'description' => $event->description,
                'type' => $event->event_type,
            ];
        })->all();

        return ['items' => $items];
    }

    public function getFleetSnapshot(?int $tenantId = null): array
    {
        $snapshot = DailyFleetMetric::query()
            ->when($tenantId !== null, fn (Builder $builder) => $builder->where('tenant_id', $tenantId))
            ->orderByDesc('captured_at')
            ->first();

        if ($snapshot !== null) {
            return [
                'timestamp' => optional($snapshot->captured_at)->toIso8601String(),
                'totals' => [
                    'active' => (int) $snapshot->active_units,
                    'available' => (int) $snapshot->available_units,
                    'total' => (int) $snapshot->total_units,
                ],
                'returnsDueToday' => (int) $snapshot->returns_due_today,
                'outstandingMaintenance' => (int) $snapshot->outstanding_maintenance,
                'utilizationPct' => round((float) $snapshot->utilization_pct, 1),
                'notes' => $snapshot->notes,
            ];
        }

        $live = $this->computeLiveFleetFigures(Carbon::now(), $tenantId);

        return [
            'timestamp' => Carbon::now()->toIso8601String(),
            'totals' => [
                'active' => $live['active_units'],
                'available' => $live['available_units'],
                'total' => $live['total_units'],
            ],
            'returnsDueToday' => $live['returns_due_today'],
            'outstandingMaintenance' => $live['outstanding_maintenance'],
            'utilizationPct' => round($live['utilization_pct'], 1),
            'notes' => null,
        ];
    }

    /**
     * Build monthly revenue & booking series.
     */
    protected function buildMonthlySeries(int $year, ?int $tenantId = null): array
    {
        $results = $this->applyTenantFilterToBookings(
            Booking::query()
                ->selectRaw('MONTH(start_date) as month_number')
                ->selectRaw('SUM(total_amount) as total_revenue')
                ->selectRaw('COUNT(*) as booking_count')
                ->whereYear('start_date', $year)
                ->groupBy('month_number'),
            $tenantId
        )->get()->keyBy('month_number');

        return collect(range(1, 12))->map(function (int $month) use ($results) {
            $row = $results->get($month);

            return [
                'month' => Carbon::create(null, $month, 1)->format('M'),
                'totalRevenue' => $row ? round((float) $row->total_revenue, 2) : 0.0,
                'bookingCount' => $row ? (int) $row->booking_count : 0,
            ];
        })->all();
    }

    /**
     * Compute live fleet figures when cached metrics are not available.
     */
    protected function computeLiveFleetFigures(Carbon $reference, ?int $tenantId = null): array
    {
        $carIds = $this->getRelevantCarIds($tenantId);
        if (empty($carIds)) {
            return [
                'total_units' => 0,
                'active_units' => 0,
                'available_units' => 0,
                'returns_due_today' => 0,
                'outstanding_maintenance' => 0,
                'utilization_pct' => 0.0,
            ];
        }

        $startOfDay = $reference->copy()->startOfDay();
        $endOfDay = $reference->copy()->endOfDay();

        $activeUnits = Booking::query()
            ->whereIn('car_id', $carIds)
            ->where('start_date', '<=', $endOfDay)
            ->where('end_date', '>=', $startOfDay)
            ->whereNotIn('status', ['Cancelled', 'Completed'])
            ->distinct('car_id')
            ->count('car_id');

        $returnsDue = Booking::query()
            ->whereIn('car_id', $carIds)
            ->whereDate('expected_return_date', $reference->toDateString())
            ->whereNotIn('status', ['Cancelled'])
            ->count();

        $totalUnits = count($carIds);
        $availableUnits = max($totalUnits - $activeUnits, 0);
        $utilization = $totalUnits > 0 ? ($activeUnits / $totalUnits) * 100 : 0.0;

        return [
            'total_units' => $totalUnits,
            'active_units' => $activeUnits,
            'available_units' => $availableUnits,
            'returns_due_today' => $returnsDue,
            'outstanding_maintenance' => 0,
            'utilization_pct' => $utilization,
        ];
    }

    /**
     * Apply tenant-specific filters to bookings queries.
     *
     * @param Builder|QueryBuilder $query
     * @param int|null $tenantId
     * @return Builder|QueryBuilder
     */
    protected function applyTenantFilterToBookings(Builder|QueryBuilder $query, ?int $tenantId)
    {
        if ($tenantId === null) {
            return $query;
        }

        $carIds = $this->resolveCarIdsForTenant($tenantId);

        if (!empty($carIds)) {
            $query->whereIn('car_id', $carIds);
        } else {
            $query->where('tenant_id', $tenantId);
        }

        return $query;
    }

    /**
     * Resolve car IDs accessible to a tenant (cached per tenant).
     *
     * @return array<int>
     */
    protected function resolveCarIdsForTenant(int $tenantId): array
    {
        if (array_key_exists($tenantId, $this->carIdsCache)) {
            return $this->carIdsCache[$tenantId];
        }

        $companyIds = Company::query()
            ->where('user_id', $tenantId)
            ->pluck('id');

        $carIds = [];
        if ($companyIds->isNotEmpty()) {
            $carIds = Car::query()
                ->whereIn('company_id', $companyIds)
                ->pluck('id')
                ->all();
        }

        if (empty($carIds)) {
            $carIds = Booking::query()
                ->where('tenant_id', $tenantId)
                ->distinct()
                ->pluck('car_id')
                ->filter()
                ->all();
        }

        return $this->carIdsCache[$tenantId] = $carIds;
    }

    /**
     * Fetch car IDs relevant for a tenant or globally (cached).
     *
     * @return array<int>
     */
    protected function getRelevantCarIds(?int $tenantId = null): array
    {
        if ($tenantId === null) {
            if ($this->allCarIdsCache === null) {
                $this->allCarIdsCache = Car::query()->pluck('id')->all();
            }

            return $this->allCarIdsCache;
        }

        return $this->resolveCarIdsForTenant($tenantId);
    }

    /**
     * Calculate percentage change between two values.
     */
    protected function calculatePercentDelta(float $current, float $previous): float
    {
        if ($previous == 0.0) {
            return round($current > 0 ? 100.0 : 0.0, 1);
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Calculate difference in percentage points between two percentages.
     */
    protected function calculatePointDelta(float $current, float $previous): float
    {
        return round($current - $previous, 1);
    }

    /**
     * Parse named range windows (e.g. "next-5-days").
     *
     * @return array{0:Carbon,1:Carbon}
     */
    protected function resolveRangeWindow(string $range): array
    {
        $now = Carbon::now();

        if (preg_match('/next-(\d+)-days/i', $range, $matches)) {
            $days = max(1, (int) $matches[1]);

            return [
                $now->copy()->startOfDay(),
                $now->copy()->addDays($days)->endOfDay(),
            ];
        }

        if (strcasecmp($range, 'this-week') === 0) {
            return [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
            ];
        }

        if (preg_match('/^between:(\d{4}-\d{2}-\d{2}),(\d{4}-\d{2}-\d{2})$/', $range, $matches)) {
            try {
                $start = Carbon::parse($matches[1])->startOfDay();
                $end = Carbon::parse($matches[2])->endOfDay();

                if ($end->lessThan($start)) {
                    [$start, $end] = [$end, $start];
                }

                return [$start, $end];
            } catch (\Throwable $e) {
                // fall through to default
            }
        }

        return [
            $now->copy()->startOfDay(),
            $now->copy()->addDays(5)->endOfDay(),
        ];
    }
}
