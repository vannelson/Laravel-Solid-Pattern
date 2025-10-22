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
