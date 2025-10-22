<?php

namespace App\Services\Reports;

use App\Models\Booking;
use App\Models\Car;
use App\Models\Payment;
use App\Models\User;
use App\Services\Concerns\ResolvesTenantScope;
use App\Services\Contracts\TenantReportServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TenantReportService implements TenantReportServiceInterface
{
    use ResolvesTenantScope;

    private const DEFAULT_BOOKING_STATUSES = ['Reserved', 'Ongoing', 'Completed'];
    private const EXCLUDED_BOOKING_STATUSES = ['Cancelled'];
    private const UNAVAILABLE_CAR_STATUSES = ['maintenance', 'out_of_service', 'out-of-service', 'inactive', 'workshop', 'repair'];

    /**
     * @inheritDoc
     */
    public function getMonthlySales(User $tenant, array $filters = []): array
    {
        $normalised = $this->normaliseFilters($filters);

        $companyScope = $this->resolveCompanyScope($tenant, $normalised['company_id']);
        if (empty($companyScope)) {
            return $this->emptyPayload($normalised);
        }

        $months = $this->buildMonthRange($normalised['range_start'], $normalised['range_end'], $normalised['timezone']);
        if ($months->isEmpty()) {
            return $this->emptyPayload($normalised);
        }

        $seriesData = $this->aggregateMonthlyMetrics($companyScope, $normalised['range_start'], $normalised['range_end']);
        $series = $this->formatSeries($months, $seriesData, $normalised['timezone']);

        $previousSeries = [];
        if ($normalised['include_previous']) {
            [$previousStart, $previousEnd] = $this->previousWindow($months, $normalised['range_start']);
            if ($previousStart !== null && $previousEnd !== null) {
                $previousMonths = $this->buildMonthRange($previousStart, $previousEnd, $normalised['timezone']);
                $previousData = $this->aggregateMonthlyMetrics($companyScope, $previousStart, $previousEnd);
                $previousSeries = $this->formatSeries($previousMonths, $previousData, $normalised['timezone']);
            }
        }

        $totalsCurrent = $this->summariseSeries($series);
        $totalsPrevious = $this->summariseSeries($previousSeries);

        return [
            'company_id' => $normalised['company_id'] ?? (count($companyScope) === 1 ? $companyScope[0] : null),
            'timezone' => $normalised['timezone'],
            'range' => [
                'mode' => $normalised['mode'],
                'start' => $normalised['range_start']->toDateString(),
                'end' => $normalised['range_end']->toDateString(),
                'as_of' => $normalised['as_of']->toDateString(),
            ],
            'currency' => $this->resolveCurrency(Arr::get($filters, 'currency'), $companyScope),
            'series' => $series,
            'previous' => $previousSeries,
            'totals' => [
                'current' => $totalsCurrent,
                'previous' => $totalsPrevious,
                'percent_change' => [
                    'actual_return' => $this->percentDelta($totalsPrevious['actual_return'], $totalsCurrent['actual_return']),
                    'completed_bookings' => $this->percentDelta($totalsPrevious['completed_bookings'], $totalsCurrent['completed_bookings']),
                    'average_booking_value' => $this->percentDelta($totalsPrevious['average_booking_value'], $totalsCurrent['average_booking_value']),
                ],
            ],
        ];
    }

    public function getHighlights(User $tenant, array $filters = []): array
    {
        $normalised = $this->normaliseHighlightFilters($filters);
        $companyScope = $this->resolveCompanyScope($tenant, $normalised['company_id']);

        if (empty($companyScope)) {
            return $this->emptyHighlightsPayload($normalised);
        }

        $salesCurrent = $this->aggregateSalesTotals($companyScope, $normalised['month_start'], $normalised['month_end']);
        $salesPrevious = $this->aggregateSalesTotals($companyScope, $normalised['previous_month_start'], $normalised['previous_month_end']);

        $availabilityCurrent = $this->calculateAvailabilitySnapshot($companyScope, $normalised['as_of']);
        $availabilityPrevious = $this->calculateAvailabilitySnapshot($companyScope, $normalised['previous_reference']);

        $progress = $this->calculateMonthProgress($normalised['as_of']);

        return [
            'company_id' => $normalised['company_id'] ?? (count($companyScope) === 1 ? $companyScope[0] : null),
            'as_of' => $normalised['as_of']->copy()->setTimezone($normalised['timezone'])->toIso8601String(),
            'timezone' => $normalised['timezone'],
            'totals' => [
                'sales' => [
                    'amount' => round($salesCurrent['amount'], 2),
                    'currency' => $this->resolveCurrency(Arr::get($filters, 'currency'), $companyScope),
                    'period_start' => $normalised['month_start']->toDateString(),
                    'period_end' => $normalised['month_end']->toDateString(),
                    'bookings' => $salesCurrent['bookings'],
                    'delta' => $normalised['include_trend']
                        ? [
                            'percent' => $this->percentDelta($salesPrevious['amount'], $salesCurrent['amount']),
                            'previous_amount' => round($salesPrevious['amount'], 2),
                            'previous_bookings' => $salesPrevious['bookings'],
                        ]
                        : null,
                ],
                'availability' => [
                    'fleet_total' => $availabilityCurrent['fleet'],
                    'available' => $availabilityCurrent['available'],
                    'active_rentals' => $availabilityCurrent['active'],
                    'unavailable' => $availabilityCurrent['unavailable'],
                    'utilization_rate' => $availabilityCurrent['utilization_rate'],
                    'delta' => $normalised['include_trend']
                        ? [
                            'percent' => $this->percentDelta($availabilityPrevious['utilization_rate'], $availabilityCurrent['utilization_rate']),
                            'previous_rate' => $availabilityPrevious['utilization_rate'],
                        ]
                        : null,
                ],
                'month_progress' => $progress,
            ],
        ];
    }

    public function getRevenueByClass(User $tenant, array $filters = []): array
    {
        $normalised = $this->normaliseRevenueFilters($filters);
        $companyScope = $this->resolveCompanyScope($tenant, $normalised['company_id']);

        if (empty($companyScope)) {
            return $this->emptyRevenuePayload($normalised);
        }

        $rows = $this->aggregateRevenueByClass($companyScope, $normalised['range_start'], $normalised['range_end']);
        $total = $rows->sum('revenue');
        $sorted = $rows->sortByDesc('revenue')->values();

        $limit = $normalised['limit'];
        $includeOthers = $normalised['include_others'];

        $items = $sorted->take($limit)->map(function (array $row) use ($total) {
            $share = $total > 0 ? round(($row['revenue'] / $total) * 100, 2) : 0.0;
            return [
                'label' => $row['label'],
                'revenue' => round($row['revenue'], 2),
                'share' => $share,
            ];
        })->values();

        $others = null;
        if ($includeOthers && $sorted->count() > $limit) {
            $othersRevenue = $sorted->slice($limit)->sum('revenue');
            if ($othersRevenue > 0) {
                $others = [
                    'label' => 'Others',
                    'revenue' => round($othersRevenue, 2),
                    'share' => $total > 0 ? round(($othersRevenue / $total) * 100, 2) : 0.0,
                ];
            }
        }

        return [
            'company_id' => $normalised['company_id'] ?? (count($companyScope) === 1 ? $companyScope[0] : null),
            'preset' => $normalised['preset'],
            'currency' => $this->resolveCurrency(Arr::get($filters, 'currency'), $companyScope),
            'total_revenue' => round($total, 2),
            'items' => $items->all(),
            'others' => $others,
        ];
    }

    public function getUtilizationSnapshot(User $tenant, array $filters = []): array
    {
        $normalised = $this->normaliseUtilizationFilters($filters);
        $companyScope = $this->resolveCompanyScope($tenant, $normalised['company_id']);

        if (empty($companyScope)) {
            return $this->emptyUtilizationPayload($normalised);
        }

        $cars = Car::query()
            ->select(['id', 'company_id', 'info_carType', 'info_availabilityStatus'])
            ->whereIn('company_id', $companyScope)
            ->get();

        $fleet = $cars->count();
        $unavailable = $this->countUnavailableCars($cars);
        $activeCarIds = $this->resolveActiveCarIds($companyScope, self::DEFAULT_BOOKING_STATUSES, $normalised['as_of']);
        $active = $activeCarIds->count();
        $available = max($fleet - $active - $unavailable, 0);
        $rate = $fleet > 0 ? round($active / $fleet, 4) : 0.0;

        $previousActiveIds = $this->resolveActiveCarIds($companyScope, self::DEFAULT_BOOKING_STATUSES, $normalised['previous_reference']);
        $previousActive = $previousActiveIds->count();
        $previousRate = $fleet > 0 ? round($previousActive / $fleet, 4) : 0.0;

        $breakdown = $normalised['include_breakdown']
            ? $this->buildUtilizationBreakdown($cars, $activeCarIds)
            : [];

        return [
            'company_id' => $normalised['company_id'] ?? (count($companyScope) === 1 ? $companyScope[0] : null),
            'as_of' => $normalised['as_of']->copy()->setTimezone($normalised['timezone'])->toIso8601String(),
            'timezone' => $normalised['timezone'],
            'totals' => [
                'fleet' => $fleet,
                'active_rentals' => $active,
                'available' => $available,
                'unavailable' => $unavailable,
                'utilization_rate' => $rate,
            ],
            'trend' => [
                'percent_change' => $this->percentDelta($previousRate, $rate),
                'previous' => [
                    'period_start' => $normalised['previous_reference']->copy()->setTimezone($normalised['timezone'])->toIso8601String(),
                    'period_end' => $normalised['previous_reference']->copy()->setTimezone($normalised['timezone'])->toIso8601String(),
                    'active_rentals' => $previousActive,
                    'utilization_rate' => $previousRate,
                ],
            ],
            'breakdown' => $normalised['include_breakdown'] ? $breakdown : null,
            'refresh' => [
                'suggested_poll_seconds' => 300,
            ],
        ];
    }

    public function getUpcomingBookings(User $tenant, array $filters = []): array
    {
        $normalised = $this->normaliseUpcomingFilters($filters);
        $companyScope = $this->resolveCompanyScope($tenant, $normalised['company_id']);

        $currency = $this->resolveCurrency(Arr::get($filters, 'currency'), $companyScope);

        if (empty($companyScope)) {
            return $this->emptyUpcomingPayload($normalised, $currency);
        }

        $appTz = config('app.timezone', 'UTC');
        $startForQuery = $normalised['start']->copy()->setTimezone($appTz)->toDateTimeString();
        $endForQuery = $normalised['end']->copy()->setTimezone($appTz)->toDateTimeString();

        $bookings = Booking::query()
            ->with(['car', 'borrower', 'company'])
            ->whereIn('company_id', $companyScope)
            ->whereNotIn('status', self::EXCLUDED_BOOKING_STATUSES)
            ->whereBetween('start_date', [$startForQuery, $endForQuery])
            ->orderBy('start_date')
            ->limit($normalised['limit'])
            ->get();

        $bookingIds = $bookings->pluck('id');

        $payments = Payment::query()
            ->whereIn('booking_id', $bookingIds)
            ->where('status', 'Paid')
            ->select('booking_id', 'amount')
            ->get()
            ->groupBy('booking_id');

        $timezone = $normalised['timezone'];

        $items = $bookings->map(function (Booking $booking) use ($payments, $currency, $timezone) {
            $bookingPaid = ($payments[$booking->id] ?? collect())->sum('amount');
            $totalAmount = (float) $booking->total_amount;
            $balance = max($totalAmount - $bookingPaid, 0);

            $pickup = Carbon::parse($booking->start_date, config('app.timezone', 'UTC'))
                ->setTimezone($timezone)
                ->toIso8601String();

            $returnReference = $booking->actual_return_date
                ?? $booking->end_date
                ?? $booking->expected_return_date
                ?? $booking->start_date;

            $dropoff = Carbon::parse($returnReference, config('app.timezone', 'UTC'))
                ->setTimezone($timezone)
                ->toIso8601String();

            $renterName = trim(implode(' ', array_filter([
                $booking->renter_first_name,
                $booking->renter_middle_name,
                $booking->renter_last_name,
            ])));

            if ($renterName === '' && $booking->borrower) {
                $renterName = trim(implode(' ', array_filter([
                    $booking->borrower->first_name ?? null,
                    $booking->borrower->last_name ?? null,
                ])));
            }

            $car = $booking->car;
            $vehicleName = $car ? $this->buildVehicleName($car) : null;

            return [
                'booking_id' => sprintf('BK-%06d', $booking->id),
                'status' => $booking->status,
                'pickup_at' => $pickup,
                'dropoff_at' => $dropoff,
                'renter' => [
                    'name' => $renterName !== '' ? $renterName : null,
                    'phone' => $booking->renter_phone_number ?? ($booking->borrower->phone_number ?? null),
                ],
                'vehicle' => [
                    'id' => $car->id ?? null,
                    'name' => $vehicleName,
                    'plate_no' => $car->info_plateNumber ?? null,
                    'class' => $car ? $this->normaliseCarTypeLabel($car->info_carType ?? '') : null,
                ],
                'pickup_location' => optional($booking->company)->address,
                'dropoff_location' => $booking->destination,
                'amount' => [
                    'currency' => $currency,
                    'total' => round($totalAmount, 2),
                    'paid' => round($bookingPaid, 2),
                    'balance' => round($balance, 2),
                ],
                'notes' => null,
            ];
        })->values();

        $waitlist = [];
        if ($normalised['include_waitlist']) {
            $waitlist = [];
        }

        return [
            'company_id' => $normalised['company_id'] ?? (count($companyScope) === 1 ? $companyScope[0] : null),
            'timezone' => $timezone,
            'window' => [
                'start' => $normalised['start']->toDateString(),
                'end' => $normalised['end']->toDateString(),
                'generated_at' => $normalised['generated_at']->copy()->setTimezone($timezone)->toIso8601String(),
            ],
            'items' => $items->all(),
            'waitlist' => $waitlist,
            'totals' => [
                'scheduled' => $items->count(),
                'waitlisted' => count($waitlist),
            ],
        ];
    }

    public function getTopPerformers(User $tenant, array $filters = []): array
    {
        $normalised = $this->normaliseTopPerformersFilters($filters);
        $companyScope = $this->resolveCompanyScope($tenant, $normalised['company_id']);

        $currency = $this->resolveCurrency(Arr::get($filters, 'currency'), $companyScope);

        if (empty($companyScope)) {
            return $this->emptyTopPerformersPayload($normalised, $currency);
        }

        $carsQuery = Car::query()
            ->whereIn('company_id', $companyScope);

        if ($normalised['vehicle_class']) {
            $carsQuery->whereRaw('LOWER(info_carType) = ?', [Str::lower($normalised['vehicle_class'])]);
        }

        $cars = $carsQuery->get();

        if ($cars->isEmpty()) {
            return $this->emptyTopPerformersPayload($normalised, $currency);
        }

        $carIds = $cars->pluck('id')->all();

        $currentMetrics = $this->collectPerformerMetrics(
            $carIds,
            $normalised['range_start'],
            $normalised['range_end'],
            $normalised['timezone']
        );

        $previousMetrics = $this->collectPerformerMetrics(
            $carIds,
            $normalised['previous_start'],
            $normalised['previous_end'],
            $normalised['timezone']
        );

        $carsById = $cars->keyBy('id');

        $items = collect($carIds)->map(function ($carId) use ($carsById, $currentMetrics, $previousMetrics) {
            $car = $carsById->get($carId);

            $current = $currentMetrics[$carId] ?? $this->emptyPerformerMetrics();
            $previous = $previousMetrics[$carId] ?? null;

            $trend = $this->buildPerformerTrend($current, $previous);

            return [
                'vehicle_id' => $carId,
                'name' => $car ? $this->buildVehicleName($car) : null,
                'plate_no' => $car->info_plateNumber ?? null,
                'class' => $car ? $this->normaliseCarTypeLabel($car->info_carType ?? '') : null,
                'image_url' => $car->profileImage ?? null,
                'metrics' => [
                    'revenue' => round($current['revenue'], 2),
                    'bookings' => $current['bookings'],
                    'occupancy_rate' => round($current['occupancy_rate'], 4),
                    'utilization_rate' => round($current['utilization_rate'], 4),
                    'avg_daily_rate' => round($current['avg_daily_rate'], 2),
                ],
                'trend' => $trend,
            ];
        });

        $metricKey = match ($normalised['metric']) {
            'occupancy' => fn ($item) => $item['metrics']['occupancy_rate'],
            'utilization' => fn ($item) => $item['metrics']['utilization_rate'],
            default => fn ($item) => $item['metrics']['revenue'],
        };

        $leaders = $items
            ->sortByDesc($metricKey)
            ->take($normalised['limit'])
            ->values();

        $leadersRevenue = $leaders->sum(fn ($item) => $item['metrics']['revenue']);
        $totalRevenue = $items->sum(fn ($item) => $item['metrics']['revenue']);

        $leadersShare = $totalRevenue > 0 ? round(($leadersRevenue / $totalRevenue) * 100, 2) : null;

        $response = [
            'company_id' => $normalised['company_id'] ?? (count($companyScope) === 1 ? $companyScope[0] : null),
            'preset' => $normalised['preset'],
            'metric' => $normalised['metric'],
            'currency' => $currency,
            'vehicle_class' => $normalised['vehicle_class'],
            'range' => [
                'start' => $normalised['range_start']->toDateString(),
                'end' => $normalised['range_end']->toDateString(),
                'as_of' => $normalised['range_end']->copy()->setTimezone($normalised['timezone'])->toIso8601String(),
            ],
            'leaders' => $leaders->all(),
        ];

        if ($normalised['include_totals']) {
            $response['totals'] = [
                'fleet_count' => $cars->count(),
                'leaders_revenue' => round($leadersRevenue, 2),
                'leaders_share_pct' => $leadersShare,
            ];
        }

        return $response;
    }

    protected function normaliseUpcomingFilters(array $filters): array
    {
        $timezone = $this->resolveTimezone(Arr::get($filters, 'timezone'));
        $companyId = Arr::has($filters, 'company_id') ? (int) Arr::get($filters, 'company_id') : null;

        $limit = (int) ($filters['limit'] ?? 10);
        $limit = max(1, min(50, $limit));

        $includeWaitlist = Arr::get($filters, 'include_waitlist');
        $includeWaitlist = $includeWaitlist === null ? false : (bool) $includeWaitlist;

        $startInput = Arr::get($filters, 'start_date');
        $endInput = Arr::get($filters, 'end_date');

        $start = $startInput
            ? Carbon::createFromFormat('Y-m-d', $startInput, $timezone)->startOfDay()
            : now($timezone)->startOfDay();

        $end = $endInput
            ? Carbon::createFromFormat('Y-m-d', $endInput, $timezone)->endOfDay()
            : $start->copy()->addDays(14)->endOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $maxEnd = $start->copy()->addDays(60)->endOfDay();
        if ($end->gt($maxEnd)) {
            $end = $maxEnd;
        }

        $generatedAt = now($timezone);

        return [
            'company_id' => $companyId,
            'timezone' => $timezone,
            'start' => $start,
            'end' => $end,
            'limit' => $limit,
            'include_waitlist' => $includeWaitlist,
            'generated_at' => $generatedAt,
        ];
    }

    protected function emptyUpcomingPayload(array $filters, string $currency): array
    {
        return [
            'company_id' => $filters['company_id'],
            'timezone' => $filters['timezone'],
            'window' => [
                'start' => $filters['start']->toDateString(),
                'end' => $filters['end']->toDateString(),
                'generated_at' => $filters['generated_at']->copy()->setTimezone($filters['timezone'])->toIso8601String(),
            ],
            'items' => [],
            'waitlist' => [],
            'totals' => [
                'scheduled' => 0,
                'waitlisted' => 0,
            ],
        ];
    }

    protected function normaliseTopPerformersFilters(array $filters): array
    {
        $timezone = $this->resolveTimezone(Arr::get($filters, 'timezone'));
        $companyId = Arr::has($filters, 'company_id') ? (int) Arr::get($filters, 'company_id') : null;
        $metric = Str::lower((string) Arr::get($filters, 'metric', 'revenue'));
        $metric = in_array($metric, ['revenue', 'occupancy', 'utilization'], true) ? $metric : 'revenue';

        $preset = Str::lower((string) Arr::get($filters, 'preset', 'rolling_30'));
        $preset = in_array($preset, ['rolling_30', 'rolling_90', 'month_to_date', 'year_to_date', 'custom'], true)
            ? $preset
            : 'rolling_30';

        $limit = (int) ($filters['limit'] ?? 5);
        $limit = max(1, min(20, $limit));

        $vehicleClass = Arr::get($filters, 'vehicle_class');
        $vehicleClass = $vehicleClass ? $this->normaliseCarTypeLabel($vehicleClass) : null;

        $includeTotals = Arr::get($filters, 'include_totals');
        $includeTotals = $includeTotals === null ? true : (bool) $includeTotals;

        $asOfInput = Arr::get($filters, 'as_of');
        $asOf = $asOfInput
            ? Carbon::createFromFormat('Y-m-d', $asOfInput, $timezone)->endOfDay()
            : now($timezone)->endOfDay();

        if ($preset === 'custom') {
            $rangeStart = Carbon::createFromFormat('Y-m-d', $filters['start_date'], $timezone)->startOfDay();
            $rangeEnd = Carbon::createFromFormat('Y-m-d', $filters['end_date'], $timezone)->endOfDay();
        } elseif ($preset === 'rolling_90') {
            $rangeEnd = $asOf->copy();
            $rangeStart = $rangeEnd->copy()->subDays(89)->startOfDay();
        } elseif ($preset === 'month_to_date') {
            $rangeEnd = $asOf->copy();
            $rangeStart = Carbon::create($asOf->year, $asOf->month, 1, 0, 0, 0, $timezone)->startOfDay();
        } elseif ($preset === 'year_to_date') {
            $rangeEnd = $asOf->copy();
            $rangeStart = Carbon::create($asOf->year, 1, 1, 0, 0, 0, $timezone)->startOfDay();
        } else { // rolling_30
            $rangeEnd = $asOf->copy();
            $rangeStart = $rangeEnd->copy()->subDays(29)->startOfDay();
        }

        if ($rangeEnd->lt($rangeStart)) {
            [$rangeStart, $rangeEnd] = [$rangeEnd->copy()->startOfDay(), $rangeStart->copy()->endOfDay()];
        }

        $totalDays = max(1, $rangeStart->diffInDays($rangeEnd) + 1);
        $previousEnd = $rangeStart->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($totalDays - 1)->startOfDay();

        return [
            'company_id' => $companyId,
            'timezone' => $timezone,
            'preset' => $preset,
            'metric' => $metric,
            'limit' => $limit,
            'vehicle_class' => $vehicleClass,
            'include_totals' => $includeTotals,
            'range_start' => $rangeStart,
            'range_end' => $rangeEnd,
            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,
        ];
    }

    protected function emptyTopPerformersPayload(array $filters, string $currency): array
    {
        $response = [
            'company_id' => $filters['company_id'],
            'preset' => $filters['preset'],
            'metric' => $filters['metric'],
            'currency' => $currency,
            'vehicle_class' => $filters['vehicle_class'],
            'range' => [
                'start' => $filters['range_start']->toDateString(),
                'end' => $filters['range_end']->toDateString(),
                'as_of' => $filters['range_end']->copy()->setTimezone($filters['timezone'])->toIso8601String(),
            ],
            'leaders' => [],
        ];

        if ($filters['include_totals']) {
            $response['totals'] = [
                'fleet_count' => 0,
                'leaders_revenue' => 0.0,
                'leaders_share_pct' => null,
            ];
        }

        return $response;
    }

    protected function collectPerformerMetrics(array $carIds, Carbon $start, Carbon $end, string $timezone): array
    {
        $metrics = [];
        foreach ($carIds as $carId) {
            $metrics[$carId] = $this->emptyPerformerMetrics();
        }

        if (empty($carIds)) {
            return $metrics;
        }

        $appTz = config('app.timezone', 'UTC');
        $startQuery = $start->copy()->setTimezone($appTz)->toDateTimeString();
        $endQuery = $end->copy()->setTimezone($appTz)->toDateTimeString();

        $bookings = Booking::query()
            ->select(['id', 'car_id', 'start_date', 'end_date', 'expected_return_date', 'actual_return_date'])
            ->whereIn('car_id', $carIds)
            ->whereNotIn('status', self::EXCLUDED_BOOKING_STATUSES)
            ->where(function ($query) use ($startQuery, $endQuery) {
                $query->whereBetween('start_date', [$startQuery, $endQuery])
                    ->orWhereBetween('end_date', [$startQuery, $endQuery])
                    ->orWhere(function ($inner) use ($startQuery, $endQuery) {
                        $inner->where('start_date', '<=', $startQuery)
                            ->where('end_date', '>=', $endQuery);
                    });
            })
            ->get();

        $totalSeconds = max(1, $start->copy()->diffInSeconds($end));
        $totalHoursRange = $totalSeconds / 3600;
        $totalDaysRange = $totalSeconds / 86400;

        foreach ($bookings as $booking) {
            $carId = (int) $booking->car_id;

            if (!isset($metrics[$carId])) {
                continue;
            }

            $bookingStart = Carbon::parse($booking->start_date, $appTz)->setTimezone($timezone);
            $returnReference = $booking->actual_return_date
                ?? $booking->end_date
                ?? $booking->expected_return_date
                ?? $booking->start_date;
            $bookingEnd = Carbon::parse($returnReference, $appTz)->setTimezone($timezone);

            if ($bookingEnd->lt($bookingStart)) {
                $bookingEnd = $bookingStart->copy();
            }

            $overlapStart = $bookingStart->greaterThan($start) ? $bookingStart : $start->copy();
            $overlapEnd = $bookingEnd->lessThan($end) ? $bookingEnd : $end->copy();

            if ($overlapEnd->lte($overlapStart)) {
                continue;
            }

            $hours = max(0, $overlapEnd->diffInSeconds($overlapStart)) / 3600;
            $metrics[$carId]['bookings'] += 1;
            $metrics[$carId]['booked_hours'] += $hours;
        }

        $revenueRows = Payment::query()
            ->join('bookings', 'payments.booking_id', '=', 'bookings.id')
            ->whereIn('bookings.car_id', $carIds)
            ->where('payments.status', 'Paid')
            ->whereBetween('payments.paid_at', [$startQuery, $endQuery])
            ->select('bookings.car_id', DB::raw('SUM(payments.amount) as revenue'))
            ->groupBy('bookings.car_id')
            ->get();

        foreach ($revenueRows as $row) {
            $carId = (int) $row->car_id;
            if (isset($metrics[$carId])) {
                $metrics[$carId]['revenue'] = (float) $row->revenue;
            }
        }

        foreach ($metrics as $carId => &$data) {
            $data['booked_days'] = $data['booked_hours'] / 24;
            $data['occupancy_rate'] = $totalDaysRange > 0 ? min(1.0, round($data['booked_days'] / $totalDaysRange, 4)) : 0.0;
            $data['utilization_rate'] = $totalHoursRange > 0 ? min(1.0, round($data['booked_hours'] / $totalHoursRange, 4)) : 0.0;
            $data['avg_daily_rate'] = $data['booked_days'] > 0 ? round($data['revenue'] / $data['booked_days'], 2) : 0.0;
        }
        unset($data);

        return $metrics;
    }

    protected function emptyPerformerMetrics(): array
    {
        return [
            'revenue' => 0.0,
            'bookings' => 0,
            'booked_hours' => 0.0,
            'booked_days' => 0.0,
            'occupancy_rate' => 0.0,
            'utilization_rate' => 0.0,
            'avg_daily_rate' => 0.0,
        ];
    }

    protected function buildPerformerTrend(array $current, ?array $previous): ?array
    {
        if ($previous === null) {
            return null;
        }

        $trend = [
            'previous_revenue' => round($previous['revenue'], 2),
            'revenue_change_pct' => $this->percentDelta($previous['revenue'], $current['revenue']),
            'previous_occupancy_rate' => round($previous['occupancy_rate'], 4),
            'occupancy_change_pct' => $this->percentDelta($previous['occupancy_rate'], $current['occupancy_rate']),
            'previous_utilization_rate' => round($previous['utilization_rate'], 4),
            'utilization_change_pct' => $this->percentDelta($previous['utilization_rate'], $current['utilization_rate']),
        ];

        $filtered = array_filter($trend, static fn ($value) => $value !== null);

        return empty($filtered) ? null : $filtered;
    }

    protected function buildVehicleName(?Car $car): ?string
    {
        if ($car === null) {
            return null;
        }

        $parts = array_filter([
            $car->info_make ?? null,
            $car->info_model ?? null,
            $car->info_year ?? null,
        ]);

        if (!empty($parts)) {
            return trim(implode(' ', $parts));
        }

        return $car->info_plateNumber ?? null;
    }

    /**
     * Normalise request filters for monthly sales.
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    protected function normaliseFilters(array $filters): array
    {
        $timezone = $this->resolveTimezone(Arr::get($filters, 'timezone'));
        $includePrevious = Arr::get($filters, 'include_previous');
        $includePrevious = $includePrevious === null ? true : (bool) $includePrevious;

        $granularity = Arr::get($filters, 'granularity', 'month');
        if ($granularity !== 'month') {
            $granularity = 'month';
        }

        $asOf = Arr::get($filters, 'as_of');
        $asOf = $asOf
            ? Carbon::createFromFormat('Y-m-d', $asOf, $timezone)->endOfDay()
            : now($timezone)->endOfDay();

        $year = Arr::get($filters, 'year');
        $startYear = Arr::get($filters, 'start_year');
        $endYear = Arr::get($filters, 'end_year');

        if ($year !== null) {
            $mode = 'single_year';
            $rangeStart = Carbon::create((int) $year, 1, 1, 0, 0, 0, $timezone)->startOfMonth();
            $rangeEnd = Carbon::create((int) $year, 12, 1, 0, 0, 0, $timezone)->endOfMonth();
        } elseif ($startYear !== null) {
            $mode = 'range';
            $startYear = (int) $startYear;
            $endYear = $endYear !== null ? (int) $endYear : $startYear;

            $rangeStart = Carbon::create($startYear, 1, 1, 0, 0, 0, $timezone)->startOfMonth();
            $rangeEnd = Carbon::create($endYear, 12, 1, 0, 0, 0, $timezone)->endOfMonth();
            if ($asOf->lt($rangeEnd)) {
                $rangeEnd = $asOf->copy()->endOfMonth();
            }

            $monthSpan = $this->diffInMonths($rangeStart, $rangeEnd) + 1;
            if ($asOf !== null && $monthSpan > 12 && ($endYear - $startYear) <= 1) {
                $rangeStart = $rangeEnd->copy()->subMonths(11)->startOfMonth();
                if ($rangeStart->lt(Carbon::create($startYear, 1, 1, 0, 0, 0, $timezone)->startOfMonth())) {
                    $rangeStart = Carbon::create($startYear, 1, 1, 0, 0, 0, $timezone)->startOfMonth();
                }
            }
        } else {
            $mode = 'rolling_12_months';
            $rangeEnd = $asOf->copy()->endOfMonth();
            $rangeStart = $rangeEnd->copy()->subMonths(11)->startOfMonth();
        }

        return [
            'mode' => $mode,
            'company_id' => Arr::has($filters, 'company_id') ? (int) Arr::get($filters, 'company_id') : null,
            'timezone' => $timezone,
            'include_previous' => $includePrevious,
            'granularity' => $granularity,
            'as_of' => $asOf->copy()->startOfDay(),
            'range_start' => $rangeStart->startOfMonth(),
            'range_end' => $rangeEnd->endOfMonth(),
        ];
    }

    protected function resolveTimezone(?string $timezone): string
    {
        $default = config('app.timezone', 'UTC');

        if (!$timezone) {
            return $default;
        }

        try {
            new \DateTimeZone($timezone);
            return $timezone;
        } catch (\Throwable $exception) {
            return $default;
        }
    }

    /**
     * @return Collection<int, Carbon>
     */
    protected function buildMonthRange(Carbon $start, Carbon $end, string $timezone): Collection
    {
        if ($start->gt($end)) {
            return collect();
        }

        $cursor = $start->copy()->startOfMonth();
        $months = collect();

        while ($cursor->lte($end)) {
            $months->push($cursor->copy()->setTimezone($timezone));
            $cursor->addMonth();
        }

        return $months;
    }

    /**
     * Aggregate payments and booking counts per month.
     *
     * @return array<string, array{actual_return: float, completed_bookings: int}>
     */
    protected function aggregateMonthlyMetrics(array $companyIds, Carbon $start, Carbon $end): array
    {
        if (empty($companyIds)) {
            return [];
        }

        $startString = $start->copy()->startOfMonth()->toDateTimeString();
        $endString = $end->copy()->endOfMonth()->toDateTimeString();

        $paymentSums = Payment::query()
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m-01') as month_key, SUM(amount) as revenue")
            ->where('status', 'Paid')
            ->whereBetween('paid_at', [$startString, $endString])
            ->whereHas('booking', function ($query) use ($companyIds) {
                $query->whereIn('company_id', $companyIds);
            })
            ->groupBy('month_key')
            ->pluck('revenue', 'month_key')
            ->map(static fn ($value) => (float) $value)
            ->all();

        $completionExpr = "COALESCE(actual_return_date, end_date, expected_return_date, start_date)";

        $bookingCounts = Booking::query()
            ->selectRaw("DATE_FORMAT({$completionExpr}, '%Y-%m-01') as month_key, COUNT(*) as total")
            ->whereIn('company_id', $companyIds)
            ->where('status', 'Completed')
            ->whereRaw("{$completionExpr} BETWEEN ? AND ?", [$startString, $endString])
            ->groupBy('month_key')
            ->pluck('total', 'month_key')
            ->map(static fn ($value) => (int) $value)
            ->all();

        $allKeys = array_unique(array_merge(array_keys($paymentSums), array_keys($bookingCounts)));

        $results = [];
        foreach ($allKeys as $monthKey) {
            $revenue = $paymentSums[$monthKey] ?? 0.0;
            $completed = $bookingCounts[$monthKey] ?? 0;

            $results[$monthKey] = [
                'actual_return' => round($revenue, 2),
                'completed_bookings' => $completed,
            ];
        }

        return $results;
    }

    /**
     * Format a keyed month dataset into API-friendly series.
     *
     * @param Collection<int, Carbon> $months
     * @param array<string, array{actual_return: float, completed_bookings: int}> $dataset
     *
     * @return array<int, array<string, mixed>>
     */
    protected function formatSeries(Collection $months, array $dataset, string $timezone): array
    {
        return $months->map(function (Carbon $month) use ($dataset, $timezone) {
            $monthKey = $month->copy()->format('Y-m-01');

            $actual = $dataset[$monthKey]['actual_return'] ?? 0.0;
            $completed = $dataset[$monthKey]['completed_bookings'] ?? 0;
            $average = $completed > 0 ? round($actual / $completed, 2) : 0.0;

            return [
                'month' => $month->copy()->startOfMonth()->format('Y-m-01'),
                'label' => $month->copy()->setTimezone($timezone)->format('M'),
                'actual_return' => round($actual, 2),
                'completed_bookings' => $completed,
                'average_booking_value' => $average,
            ];
        })->all();
    }

    /**
     * Compute totals for a series.
     *
     * @param array<int, array<string, mixed>> $series
     *
     * @return array<string, float|int>
     */
    protected function summariseSeries(array $series): array
    {
        if (empty($series)) {
            return [
                'actual_return' => 0.0,
                'completed_bookings' => 0,
                'average_booking_value' => 0.0,
            ];
        }

        $revenue = 0.0;
        $count = 0;

        foreach ($series as $entry) {
            $revenue += (float) ($entry['actual_return'] ?? 0);
            $count += (int) ($entry['completed_bookings'] ?? 0);
        }

        $revenue = round($revenue, 2);
        $average = $count > 0 ? round($revenue / $count, 2) : 0.0;

        return [
            'actual_return' => $revenue,
            'completed_bookings' => $count,
            'average_booking_value' => $average,
        ];
    }

    /**
     * @return array{0:?Carbon,1:?Carbon}
     */
    protected function previousWindow(Collection $currentMonths, Carbon $currentStart): array
    {
        $count = $currentMonths->count();
        if ($count === 0) {
            return [null, null];
        }

        $previousEnd = $currentStart->copy()->subDay()->endOfMonth();
        $previousStart = $previousEnd->copy()->subMonths($count - 1)->startOfMonth();

        return [$previousStart, $previousEnd];
    }

    protected function percentDelta($previous, $current): ?float
    {
        $previous = (float) $previous;
        if ($previous === 0.0) {
            return null;
        }

        $delta = (($current - $previous) / $previous) * 100;

        return round($delta, 2);
    }

    protected function normaliseHighlightFilters(array $filters): array
    {
        $timezone = $this->resolveTimezone(Arr::get($filters, 'timezone'));
        $companyId = Arr::has($filters, 'company_id') ? (int) Arr::get($filters, 'company_id') : null;
        $includeTrend = Arr::get($filters, 'include_trend');
        $includeTrend = $includeTrend === null ? true : (bool) $includeTrend;

        $asOfInput = Arr::get($filters, 'as_of');
        $asOf = $asOfInput
            ? Carbon::createFromFormat('Y-m-d', $asOfInput, $timezone)->endOfDay()
            : now($timezone)->endOfDay();

        $monthStart = $asOf->copy()->startOfMonth();
        $monthEnd = $asOf->copy()->endOfMonth();

        $previousMonthEnd = $monthStart->copy()->subDay()->endOfDay();
        $previousMonthStart = $previousMonthEnd->copy()->startOfMonth();
        $previousReference = $asOf->copy()->subMonthNoOverflow();

        return [
            'company_id' => $companyId,
            'timezone' => $timezone,
            'include_trend' => $includeTrend,
            'as_of' => $asOf,
            'month_start' => $monthStart,
            'month_end' => $monthEnd,
            'previous_month_start' => $previousMonthStart,
            'previous_month_end' => $previousMonthEnd,
            'previous_reference' => $previousReference,
        ];
    }

    protected function emptyHighlightsPayload(array $filters): array
    {
        return [
            'company_id' => $filters['company_id'],
            'as_of' => $filters['as_of']->copy()->setTimezone($filters['timezone'])->toIso8601String(),
            'timezone' => $filters['timezone'],
            'totals' => [
                'sales' => [
                    'amount' => 0.0,
                    'currency' => $this->resolveCurrency(null, []),
                    'period_start' => $filters['month_start']->toDateString(),
                    'period_end' => $filters['month_end']->toDateString(),
                    'bookings' => 0,
                    'delta' => $filters['include_trend']
                        ? [
                            'percent' => null,
                            'previous_amount' => 0.0,
                            'previous_bookings' => 0,
                        ]
                        : null,
                ],
                'availability' => [
                    'fleet_total' => 0,
                    'available' => 0,
                    'active_rentals' => 0,
                    'unavailable' => 0,
                    'utilization_rate' => 0.0,
                    'delta' => $filters['include_trend']
                        ? [
                            'percent' => null,
                            'previous_rate' => 0.0,
                        ]
                        : null,
                ],
                'month_progress' => $this->calculateMonthProgress($filters['as_of']),
            ],
        ];
    }

    protected function aggregateSalesTotals(array $companyIds, Carbon $start, Carbon $end): array
    {
        if (empty($companyIds)) {
            return ['amount' => 0.0, 'bookings' => 0];
        }

        $startString = $start->copy()->startOfDay()->toDateTimeString();
        $endString = $end->copy()->endOfDay()->toDateTimeString();

        $amount = Payment::query()
            ->where('status', 'Paid')
            ->whereBetween('paid_at', [$startString, $endString])
            ->whereHas('booking', function ($query) use ($companyIds) {
                $query->whereIn('company_id', $companyIds);
            })
            ->sum('amount');

        $completionExpr = "COALESCE(actual_return_date, end_date, expected_return_date, start_date)";
        $bookings = Booking::query()
            ->whereIn('company_id', $companyIds)
            ->where('status', 'Completed')
            ->whereRaw("{$completionExpr} BETWEEN ? AND ?", [$startString, $endString])
            ->count();

        return [
            'amount' => (float) $amount,
            'bookings' => (int) $bookings,
        ];
    }

    protected function calculateAvailabilitySnapshot(array $companyIds, Carbon $reference): array
    {
        $cars = Car::query()
            ->select(['id', 'company_id', 'info_carType', 'info_availabilityStatus'])
            ->whereIn('company_id', $companyIds)
            ->get();

        $fleet = $cars->count();
        $unavailable = $this->countUnavailableCars($cars);

        $activeIds = $this->resolveActiveCarIds($companyIds, self::DEFAULT_BOOKING_STATUSES, $reference);
        $active = $activeIds->count();
        $available = max($fleet - $active - $unavailable, 0);
        $rate = $fleet > 0 ? round($active / $fleet, 4) : 0.0;

        return [
            'fleet' => $fleet,
            'active' => $active,
            'available' => $available,
            'unavailable' => $unavailable,
            'utilization_rate' => $rate,
        ];
    }

    protected function calculateMonthProgress(Carbon $asOf): array
    {
        $start = $asOf->copy()->startOfMonth();
        $end = $asOf->copy()->endOfMonth();

        $totalDays = $end->diffInDays($start) + 1;
        $daysElapsed = min($totalDays, $asOf->diffInDays($start) + 1);
        $daysRemaining = max($totalDays - $daysElapsed, 0);

        return [
            'month' => $start->toDateString(),
            'days_elapsed' => $daysElapsed,
            'days_remaining' => $daysRemaining,
            'total_days' => $totalDays,
        ];
    }

    protected function normaliseRevenueFilters(array $filters): array
    {
        $timezone = $this->resolveTimezone(Arr::get($filters, 'timezone'));
        $companyId = Arr::has($filters, 'company_id') ? (int) Arr::get($filters, 'company_id') : null;
        $includeOthers = Arr::get($filters, 'include_others');
        $includeOthers = $includeOthers === null ? true : (bool) $includeOthers;
        $limit = max(1, (int) ($filters['limit'] ?? 10));

        $preset = Str::snake((string) ($filters['preset'] ?? 'year_to_date'));
        $preset = in_array($preset, ['year_to_date', 'last_90_days', 'custom'], true) ? $preset : 'year_to_date';

        $asOf = Arr::get($filters, 'as_of');
        $asOf = $asOf
            ? Carbon::createFromFormat('Y-m-d', $asOf, $timezone)->endOfDay()
            : now($timezone)->endOfDay();

        if ($preset === 'custom') {
            $startDate = Carbon::createFromFormat('Y-m-d', $filters['start_date'], $timezone)->startOfDay();
            $endDate = Carbon::createFromFormat('Y-m-d', $filters['end_date'], $timezone)->endOfDay();
        } elseif ($preset === 'last_90_days') {
            $endDate = $asOf->copy();
            $startDate = $endDate->copy()->subDays(89)->startOfDay();
        } else {
            $endDate = $asOf->copy();
            $startDate = Carbon::create($asOf->year, 1, 1, 0, 0, 0, $timezone)->startOfDay();
        }

        return [
            'company_id' => $companyId,
            'preset' => $preset,
            'timezone' => $timezone,
            'range_start' => $startDate,
            'range_end' => $endDate,
            'include_others' => $includeOthers,
            'limit' => $limit,
        ];
    }

    protected function emptyRevenuePayload(array $filters): array
    {
        return [
            'company_id' => $filters['company_id'],
            'preset' => $filters['preset'],
            'currency' => $this->resolveCurrency(null, []),
            'total_revenue' => 0.0,
            'items' => [],
            'others' => null,
        ];
    }

    protected function aggregateRevenueByClass(array $companyIds, Carbon $start, Carbon $end): Collection
    {
        if (empty($companyIds)) {
            return collect();
        }

        $startString = $start->copy()->startOfDay()->toDateTimeString();
        $endString = $end->copy()->endOfDay()->toDateTimeString();

        return Payment::query()
            ->join('bookings', 'payments.booking_id', '=', 'bookings.id')
            ->leftJoin('cars', 'bookings.car_id', '=', 'cars.id')
            ->where('payments.status', 'Paid')
            ->whereIn('bookings.company_id', $companyIds)
            ->whereBetween('payments.paid_at', [$startString, $endString])
            ->selectRaw("COALESCE(NULLIF(TRIM(cars.info_carType), ''), 'Unspecified') as type_label, SUM(payments.amount) as revenue")
            ->groupBy('type_label')
            ->get()
            ->map(function ($row) {
                return [
                    'label' => $this->normaliseCarTypeLabel($row->type_label),
                    'revenue' => (float) $row->revenue,
                ];
            });
    }

    protected function normaliseUtilizationFilters(array $filters): array
    {
        $timezone = $this->resolveTimezone(Arr::get($filters, 'timezone'));
        $companyId = Arr::has($filters, 'company_id') ? (int) Arr::get($filters, 'company_id') : null;
        $includeBreakdown = Arr::get($filters, 'include_breakdown');
        $includeBreakdown = $includeBreakdown === null ? true : (bool) $includeBreakdown;

        $asOfInput = Arr::get($filters, 'as_of');
        $asOf = $asOfInput
            ? Carbon::parse($asOfInput, $timezone)
            : now($timezone);

        $previousReference = $asOf->copy()->subDay();

        return [
            'company_id' => $companyId,
            'timezone' => $timezone,
            'include_breakdown' => $includeBreakdown,
            'as_of' => $asOf,
            'previous_reference' => $previousReference,
        ];
    }

    protected function emptyUtilizationPayload(array $filters): array
    {
        return [
            'company_id' => $filters['company_id'],
            'as_of' => $filters['as_of']->copy()->setTimezone($filters['timezone'])->toIso8601String(),
            'timezone' => $filters['timezone'],
            'totals' => [
                'fleet' => 0,
                'active_rentals' => 0,
                'available' => 0,
                'unavailable' => 0,
                'utilization_rate' => 0.0,
            ],
            'trend' => [
                'percent_change' => null,
                'previous' => [
                    'period_start' => $filters['previous_reference']->copy()->setTimezone($filters['timezone'])->toIso8601String(),
                    'period_end' => $filters['previous_reference']->copy()->setTimezone($filters['timezone'])->toIso8601String(),
                    'active_rentals' => 0,
                    'utilization_rate' => 0.0,
                ],
            ],
            'breakdown' => $filters['include_breakdown'] ? [] : null,
            'refresh' => [
                'suggested_poll_seconds' => 300,
            ],
        ];
    }

    protected function resolveActiveCarIds(array $companyIds, array $statuses, Carbon $reference): Collection
    {
        if (empty($companyIds)) {
            return collect();
        }

        $referenceString = $reference->copy()->toDateTimeString();
        $completionExpr = "COALESCE(actual_return_date, end_date, expected_return_date, start_date)";

        return Booking::query()
            ->select('car_id')
            ->whereNotNull('car_id')
            ->whereIn('company_id', $companyIds)
            ->whereIn('status', $statuses)
            ->whereNotIn('status', self::EXCLUDED_BOOKING_STATUSES)
            ->where(function ($query) use ($referenceString, $completionExpr) {
                $query->where('start_date', '<=', $referenceString)
                    ->whereRaw("{$completionExpr} >= ?", [$referenceString]);
            })
            ->pluck('car_id')
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    protected function countUnavailableCars(Collection $cars): int
    {
        return $cars->filter(fn ($car) => $this->isCarUnavailable($car))->count();
    }

    protected function isCarUnavailable($car): bool
    {
        $status = mb_strtolower(trim((string) ($car->info_availabilityStatus ?? '')));

        return $status !== '' && in_array($status, self::UNAVAILABLE_CAR_STATUSES, true);
    }

    protected function buildUtilizationBreakdown(Collection $cars, Collection $activeCarIds): array
    {
        if ($cars->isEmpty()) {
            return [];
        }

        $activeLookup = $activeCarIds->flip()->all();

        return $cars
            ->groupBy(function ($car) {
                return $this->normaliseCarTypeLabel($car->info_carType ?? '');
            })
            ->map(function (Collection $group, string $label) use ($activeLookup) {
                $fleet = $group->count();
                $active = $group->filter(fn ($car) => isset($activeLookup[(int) $car->id]))->count();
                $unavailable = $group->filter(fn ($car) => $this->isCarUnavailable($car))->count();
                $available = max($fleet - $active - $unavailable, 0);
                $rate = $fleet > 0 ? round($active / $fleet, 2) : 0.0;

                return [
                    'label' => $label,
                    'fleet' => $fleet,
                    'active' => $active,
                    'available' => $available,
                    'utilization' => $rate,
                ];
            })
            ->sortByDesc('utilization')
            ->values()
            ->all();
    }

    protected function normaliseCarTypeLabel(?string $value): string
    {
        $label = Str::of((string) $value)->trim()->squish()->__toString();
        if ($label === '') {
            return 'Unspecified';
        }

        $words = preg_split('/\s+/', $label) ?: [];
        $normalised = array_map(function ($word) {
            $word = trim($word);
            if ($word === '') {
                return null;
            }

            if (mb_strtoupper($word) === $word) {
                return mb_strtoupper($word);
            }

            return Str::ucfirst(Str::lower($word));
        }, $words);

        return implode(' ', array_filter($normalised));
    }

    /**
     * Provide an empty payload when no companies are accessible.
     *
     * @param array<string, mixed> $filters
     */
    protected function emptyPayload(array $filters): array
    {
        return [
            'company_id' => $filters['company_id'] ?? null,
            'timezone' => $filters['timezone'],
            'range' => [
                'mode' => $filters['mode'],
                'start' => $filters['range_start']->toDateString(),
                'end' => $filters['range_end']->toDateString(),
                'as_of' => $filters['as_of']->toDateString(),
            ],
            'currency' => $this->resolveCurrency(null, []),
            'series' => [],
            'previous' => [],
            'totals' => [
                'current' => [
                    'actual_return' => 0.0,
                    'completed_bookings' => 0,
                    'average_booking_value' => 0.0,
                ],
                'previous' => [
                    'actual_return' => 0.0,
                    'completed_bookings' => 0,
                    'average_booking_value' => 0.0,
                ],
                'percent_change' => [
                    'actual_return' => null,
                    'completed_bookings' => null,
                    'average_booking_value' => null,
                ],
            ],
        ];
    }

    protected function diffInMonths(Carbon $start, Carbon $end): int
    {
        return (($end->year - $start->year) * 12) + ($end->month - $start->month);
    }
}
