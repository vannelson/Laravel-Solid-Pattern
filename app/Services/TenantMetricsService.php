<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Car;
use App\Models\Company;
use App\Models\Payment;
use App\Models\User;
use App\Services\Contracts\TenantMetricsServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TenantMetricsService implements TenantMetricsServiceInterface
{
    private const CACHE_TTL_MINUTES = 5;

    private const DEFAULT_STATUSES = ['Reserved', 'Ongoing', 'Completed'];

    private const EXCLUDED_STATUSES = ['Cancelled'];

    private const ALLOWED_STATUSES = [
        'Reserved',
        'Ongoing',
        'Completed',
        'Confirmed',
        'Pending',
    ];

    private const PRESETS = [
        'year_to_date',
        'last_30_days',
        'quarter_to_date',
        'custom',
    ];

    private const UNAVAILABLE_STATUSES = [
        'maintenance',
        'out_of_service',
        'out-of-service',
        'inactive',
        'workshop',
        'repair',
    ];

    private const DATE_FIELD_MAP = [
        'actual_return'       => ['column' => 'actual_return_date', 'fallback' => 'end_date'],
        'actual_return_date'  => ['column' => 'actual_return_date', 'fallback' => 'end_date'],
        'end_date'            => ['column' => 'end_date'],
        'expected_return'     => ['column' => 'expected_return_date'],
        'expected_return_date'=> ['column' => 'expected_return_date'],
        'start_date'          => ['column' => 'start_date'],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDashboardSummary(User $tenant, array $filters = []): array
    {
        $normalized = $this->normaliseFilters($filters);

        $cacheKey = $this->makeCacheKey((int) $tenant->getKey(), $normalized);

        $ttl = now()->addMinutes(self::CACHE_TTL_MINUTES);

        return Cache::remember($cacheKey, $ttl, function () use ($tenant, $normalized) {
            return $this->buildSummary($tenant, $normalized);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getFleetUtilization(User $tenant, array $filters = []): array
    {
        $normalized = $this->normaliseUtilizationFilters($filters);

        $cacheKey = $this->makeUtilizationCacheKey((int) $tenant->getKey(), $normalized);
        $ttl = now()->addMinutes(self::CACHE_TTL_MINUTES);

        return Cache::remember($cacheKey, $ttl, function () use ($tenant, $normalized) {
            return $this->buildFleetUtilization($tenant, $normalized);
        });
    }

    /**
     * Build the summary once filters are normalised.
     *
     * @param User                     $tenant
     * @param array<string, mixed>     $filters
     *
     * @return array<string, mixed>
     *
     * @throws AuthorizationException
     */
    protected function buildSummary(User $tenant, array $filters): array
    {
        $now = now('Asia/Manila');
        $year = (int) $filters['year'];
        [$rangeStart, $rangeEnd] = $this->resolveDateRange(
            $year,
            $filters['preset'],
            $filters['custom_start'],
            $filters['custom_end'],
            $now
        );

        $companyScope = $this->resolveCompanyScope($tenant, $filters['company_id']);

        if (empty($companyScope)) {
            return $this->emptySummary($filters, $rangeStart, $rangeEnd, $this->resolveCurrency($filters['currency'], []));
        }

        $carId = $this->assertCarScope($filters['car_id'], $companyScope);

        $bookingsQuery = $this->buildBookingsQuery(
            $companyScope,
            $filters['statuses'],
            $filters['date_column'],
            $filters['date_fallback'],
            $rangeStart->format('Y-m-d H:i:s'),
            $rangeEnd->format('Y-m-d H:i:s'),
            $carId
        );

        $bookingsCount = (clone $bookingsQuery)->count('bookings.id');

        $revenue = $filters['use_payments']
            ? $this->aggregatePaymentsRevenue($bookingsQuery, $rangeStart->format('Y-m-d H:i:s'), $rangeEnd->format('Y-m-d H:i:s'))
            : (float) (clone $bookingsQuery)->sum('total_amount');

        $annualRevenue = $this->roundMoney($revenue);
        $averageBookingValue = $bookingsCount > 0
            ? $this->roundMoney($annualRevenue / $bookingsCount)
            : 0.0;

        $currency = $this->resolveCurrency($filters['currency'], $companyScope);

        $summary = [
            'period' => [
                'year'     => $year,
                'currency' => $currency,
            ],
            'resolvedRange' => [
                'start' => $rangeStart->toIso8601String(),
                'end'   => $rangeEnd->toIso8601String(),
            ],
            'totals' => [
                'annualRevenue'      => $annualRevenue,
                'bookingsYtd'        => $bookingsCount,
                'averageBookingValue'=> $averageBookingValue,
            ],
            'meta' => [
                'source'          => $filters['use_payments'] ? 'payments' : 'bookings',
                'statusesCounted' => $filters['statuses'],
                'generatedAt'     => now('Asia/Manila')->toIso8601String(),
            ],
            'trend' => $filters['include_trend']
                ? $this->calculateTrend(
                    $companyScope,
                    $filters,
                    $carId,
                    $rangeStart,
                    $rangeEnd,
                    $annualRevenue,
                    $bookingsCount,
                    $averageBookingValue
                )
                : null,
        ];

        if (!$filters['include_trend']) {
            unset($summary['trend']);
        }

        return $summary;
    }

    /**
     * Build the base bookings query once scoping and ranges are known.
     *
     * @param array<int>  $companyIds
     * @param array<int,string> $statuses
     * @param string      $dateColumn
     * @param string|null $dateFallback
     * @param string      $rangeStart
     * @param string      $rangeEnd
     * @param int|null    $carId
     *
     * @return Builder
     */
    protected function buildBookingsQuery(
        array $companyIds,
        array $statuses,
        string $dateColumn,
        ?string $dateFallback,
        string $rangeStart,
        string $rangeEnd,
        ?int $carId
    ): Builder {
        $query = Booking::query()
            ->select('bookings.*')
            ->whereIn('status', $statuses)
            ->whereNotIn('status', self::EXCLUDED_STATUSES)
            ->whereIn('company_id', $companyIds);

        if ($carId !== null) {
            $query->where('car_id', $carId);
        }

        $this->applyDateRangeConstraint($query, $dateColumn, $dateFallback, $rangeStart, $rangeEnd);

        return $query;
    }

    /**
     * Ensure the car filter stays within the tenant's company scope.
     *
     * @param int|null    $carId
     * @param array<int>  $companyIds
     *
     * @return int|null
     *
     * @throws AuthorizationException
     */
    protected function assertCarScope(?int $carId, array $companyIds): ?int
    {
        if ($carId === null) {
            return null;
        }

        $car = Car::query()->select(['id', 'company_id'])->find($carId);

        if ($car === null || !in_array((int) $car->company_id, $companyIds, true)) {
            throw new AuthorizationException('You are not allowed to access the requested car.');
        }

        return (int) $car->id;
    }

    /**
     * Resolve the date range based on the requested preset and custom inputs.
     */
    protected function resolveDateRange(
        int $year,
        string $preset,
        ?string $customStart,
        ?string $customEnd,
        Carbon $now
    ): array {
        $yearStart = Carbon::create($year, 1, 1, 0, 0, 0, 'Asia/Manila')->startOfDay();
        $yearEnd = (clone $yearStart)->endOfYear();

        $clampedNow = $year === (int) $now->year
            ? $now->copy()->endOfDay()
            : $yearEnd->copy();

        switch ($preset) {
            case 'last_30_days':
                $end = $clampedNow->copy();
                $start = $end->copy()->subDays(29)->startOfDay();
                break;
            case 'quarter_to_date':
                $anchor = $clampedNow->copy();
                $quarterStartMonth = (int) (floor(($anchor->quarter - 1) / 1) * 3 + 1);
                $start = Carbon::create($anchor->year, $quarterStartMonth, 1, 0, 0, 0, 'Asia/Manila')->startOfDay();
                $end = $clampedNow->copy();
                break;
            case 'custom':
                $start = $customStart
                    ? Carbon::createFromFormat('Y-m-d', $customStart, 'Asia/Manila')->startOfDay()
                    : $yearStart->copy();
                $end = $customEnd
                    ? Carbon::createFromFormat('Y-m-d', $customEnd, 'Asia/Manila')->endOfDay()
                    : $yearEnd->copy();
                break;
            case 'year_to_date':
            default:
                $start = $yearStart->copy();
                $end = $clampedNow->copy();
                break;
        }

        $start = $start->lessThan($yearStart) ? $yearStart->copy() : $start;
        $end = $end->greaterThan($yearEnd) ? $yearEnd->copy() : $end;

        if ($start->greaterThan($end)) {
            $start = $yearStart->copy();
        }

        return [$start, $end];
    }

    /**
     * Collate summary metrics for a given date window.
     */
    protected function summariseRange(
        array $companyScope,
        array $filters,
        ?int $carId,
        Carbon $rangeStart,
        Carbon $rangeEnd
    ): array {
        $query = $this->buildBookingsQuery(
            $companyScope,
            $filters['statuses'],
            $filters['date_column'],
            $filters['date_fallback'],
            $rangeStart->format('Y-m-d H:i:s'),
            $rangeEnd->format('Y-m-d H:i:s'),
            $carId
        );

        $count = (clone $query)->count('bookings.id');
        $revenue = $filters['use_payments']
            ? $this->aggregatePaymentsRevenue($query, $rangeStart->format('Y-m-d H:i:s'), $rangeEnd->format('Y-m-d H:i:s'))
            : (float) (clone $query)->sum('total_amount');

        $revenue = $this->roundMoney($revenue);
        $average = $count > 0 ? $this->roundMoney($revenue / $count) : 0.0;

        return [
            'revenue' => $revenue,
            'count' => $count,
            'average' => $average,
        ];
    }

    /**
     * Build the fleet utilisation payload.
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    protected function buildFleetUtilization(User $tenant, array $filters): array
    {
        $companyScope = $this->resolveCompanyScope($tenant, $filters['company_id']);

        if (empty($companyScope)) {
            return $this->emptyFleetUtilization($filters);
        }

        $cars = Car::query()
            ->select(['id', 'company_id', 'info_carType', 'info_availabilityStatus'])
            ->whereIn('company_id', $companyScope)
            ->get();

        $fleet = $cars->count();
        $unavailable = $this->countUnavailableCars($cars);

        $activeCarIds = $this->resolveActiveCarIds($companyScope, $filters['statuses'], $filters['as_of']);
        $activeCount = $activeCarIds->count();
        $availableCount = max($fleet - $activeCount - $unavailable, 0);
        $rate = $fleet > 0 ? round($activeCount / $fleet, 4) : 0.0;

        $companyIdForResponse = $filters['company_id'] ?? (count($companyScope) === 1 ? $companyScope[0] : null);

        $response = [
            'company_id' => $companyIdForResponse,
            'preset' => $filters['preset'],
            'as_of' => $filters['as_of']->copy()->setTimezone($filters['response_timezone'])->toIso8601String(),
            'totals' => [
                'fleet' => $fleet,
                'active_rentals' => $activeCount,
                'available' => $availableCount,
                'unavailable' => $unavailable,
            ],
            'utilization' => [
                'rate' => $rate,
                'label' => $this->formatPercentage($rate),
            ],
            'refresh' => [
                'next_poll_seconds' => 300,
                'granularity' => $filters['granularity'],
            ],
            'breakdown' => $this->buildUtilizationBreakdown($cars, $activeCarIds),
        ];

        if ($filters['include_trend']) {
            [$previousStart, $previousEnd, $previousReference] = $this->resolvePreviousWindow(
                $filters['granularity'],
                $filters['as_of']
            );

            $previousActiveCarIds = $this->resolveActiveCarIds($companyScope, $filters['statuses'], $previousReference);
            $previousActive = $previousActiveCarIds->count();
            $previousRate = $fleet > 0 ? round($previousActive / $fleet, 4) : 0.0;
            $percentChange = $previousRate > 0
                ? round((($rate - $previousRate) / $previousRate) * 100, 2)
                : null;

            $response['utilization']['trend'] = [
                'percent_change' => $percentChange,
                'previous' => [
                    'period' => [
                        'start' => $previousStart->copy()->setTimezone($filters['response_timezone'])->toDateString(),
                        'end'   => $previousEnd->copy()->setTimezone($filters['response_timezone'])->toDateString(),
                    ],
                    'rate' => $previousRate,
                    'active_rentals' => $previousActive,
                ],
            ];
        }

        return $response;
    }

    /**
     * Build an empty fleet utilisation payload when no companies are accessible.
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    protected function emptyFleetUtilization(array $filters): array
    {
        $response = [
            'company_id' => $filters['company_id'] ?? null,
            'preset' => $filters['preset'],
            'as_of' => $filters['as_of']->copy()->setTimezone($filters['response_timezone'])->toIso8601String(),
            'totals' => [
                'fleet' => 0,
                'active_rentals' => 0,
                'available' => 0,
                'unavailable' => 0,
            ],
            'utilization' => [
                'rate' => 0.0,
                'label' => $this->formatPercentage(0.0),
            ],
            'refresh' => [
                'next_poll_seconds' => 300,
                'granularity' => $filters['granularity'],
            ],
            'breakdown' => [],
        ];

        if ($filters['include_trend']) {
            [$previousStart, $previousEnd] = $this->resolvePreviousWindow($filters['granularity'], $filters['as_of']);

            $response['utilization']['trend'] = [
                'percent_change' => null,
                'previous' => [
                    'period' => [
                        'start' => $previousStart->copy()->setTimezone($filters['response_timezone'])->toDateString(),
                        'end'   => $previousEnd->copy()->setTimezone($filters['response_timezone'])->toDateString(),
                    ],
                    'rate' => 0.0,
                    'active_rentals' => 0,
                ],
            ];
        }

        return $response;
    }

    /**
     * Determine active car IDs for the supplied company scope at a reference time.
     *
     * @param array<int>          $companyIds
     * @param array<int, string>  $statuses
     */
    protected function resolveActiveCarIds(array $companyIds, array $statuses, Carbon $reference): Collection
    {
        if (empty($companyIds)) {
            return collect();
        }

        $referenceString = $reference->format('Y-m-d H:i:s');

        return Booking::query()
            ->select('car_id')
            ->whereNotNull('car_id')
            ->whereIn('company_id', $companyIds)
            ->whereIn('status', $statuses)
            ->whereNotIn('status', self::EXCLUDED_STATUSES)
            ->where(function ($query) use ($referenceString) {
                $query->where('start_date', '<=', $referenceString)
                    ->whereRaw('COALESCE(actual_return_date, end_date, expected_return_date, start_date) >= ?', [$referenceString]);
            })
            ->pluck('car_id')
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * Count cars flagged as unavailable based on their availability status.
     */
    protected function countUnavailableCars(Collection $cars): int
    {
        if ($cars->isEmpty()) {
            return 0;
        }

        $statuses = array_map('mb_strtolower', self::UNAVAILABLE_STATUSES);

        return $cars->filter(static function ($car) use ($statuses) {
            $status = $car->info_availabilityStatus ?? '';
            return in_array(mb_strtolower((string) $status), $statuses, true);
        })->count();
    }

    /**
     * Build utilisation breakdown by car classification.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildUtilizationBreakdown(Collection $cars, Collection $activeCarIds): array
    {
        if ($cars->isEmpty()) {
            return [];
        }

        $activeLookup = $activeCarIds
            ->mapWithKeys(static fn ($id) => [(int) $id => true])
            ->all();

        return $cars
            ->groupBy(function ($car) {
                $label = trim((string) ($car->info_carType ?? 'Unspecified'));
                return $label !== '' ? $label : 'Unspecified';
            })
            ->map(function (Collection $group, string $label) use ($activeLookup) {
                $fleet = $group->count();
                $active = $group->filter(static function ($car) use ($activeLookup) {
                    return isset($activeLookup[(int) $car->id]);
                })->count();
                $rate = $fleet > 0 ? round($active / $fleet, 4) : 0.0;

                return [
                    'label' => $label,
                    'utilization' => $rate,
                    'active' => $active,
                    'fleet' => $fleet,
                ];
            })
            ->sortByDesc('utilization')
            ->values()
            ->map(static function (array $item) {
                $item['utilization'] = round((float) $item['utilization'], 2);
                return $item;
            })
            ->all();
    }

    /**
     * Resolve the previous comparison window for utilisation trends.
     *
     * @return array{0:Carbon,1:Carbon,2:Carbon}
     */
    protected function resolvePreviousWindow(string $granularity, Carbon $currentReference): array
    {
        if ($granularity === 'hour') {
            $currentStart = $currentReference->copy()->startOfHour();
            $currentEnd = $currentReference->copy()->endOfHour();
            $previousEnd = $currentStart->copy()->subSecond();
            $previousStart = $previousEnd->copy()->startOfHour();
        } else {
            $currentStart = $currentReference->copy()->startOfDay();
            $currentEnd = $currentReference->copy()->endOfDay();
            $previousEnd = $currentStart->copy()->subSecond();
            $previousStart = $previousEnd->copy()->startOfDay();
        }

        return [$previousStart, $previousEnd, $previousEnd->copy()];
    }

    /**
     * Format a ratio as a percentage string.
     */
    protected function formatPercentage(float $ratio, int $precision = 2): string
    {
        return number_format($ratio * 100, $precision) . '%';
    }

    /**
     * Calculate trend information when requested.
     */
    protected function calculateTrend(
        array $companyScope,
        array $filters,
        ?int $carId,
        Carbon $currentStart,
        Carbon $currentEnd,
        float $currentRevenue,
        int $currentCount,
        float $currentAverage
    ): ?array {
        $rangeDays = max(1, $currentStart->diffInDays($currentEnd) + 1);

        $previousEnd = $currentStart->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($rangeDays - 1)->startOfDay();

        if ($previousStart->gt($previousEnd)) {
            return [
                'previous' => [
                    'annualRevenue' => 0.0,
                    'bookingsYtd' => 0,
                    'averageBookingValue' => 0.0,
                    'period' => null,
                ],
                'percentChange' => [
                    'annualRevenue' => null,
                    'bookingsYtd' => null,
                    'averageBookingValue' => null,
                ],
            ];
        }

        $previousSummary = $this->summariseRange(
            $companyScope,
            $filters,
            $carId,
            $previousStart,
            $previousEnd
        );

        return [
            'previous' => [
                'annualRevenue' => $previousSummary['revenue'],
                'bookingsYtd' => $previousSummary['count'],
                'averageBookingValue' => $previousSummary['average'],
                'period' => $previousSummary['count'] > 0 || $previousSummary['revenue'] > 0
                    ? [
                        'start' => $previousStart->toIso8601String(),
                        'end'   => $previousEnd->toIso8601String(),
                    ]
                    : null,
            ],
            'percentChange' => [
                'annualRevenue' => $this->calculatePercentChange($previousSummary['revenue'], $currentRevenue),
                'bookingsYtd' => $this->calculatePercentChange($previousSummary['count'], $currentCount),
                'averageBookingValue' => $this->calculatePercentChange($previousSummary['average'], $currentAverage),
            ],
        ];
    }

    /**
     * Calculate percentage change between two values.
     */
    protected function calculatePercentChange($previous, $current): ?float
    {
        if ((float) $previous === 0.0) {
            return null;
        }

        $change = (($current - $previous) / $previous) * 100;

        return $this->roundMoney($change);
    }

    protected function resolveCurrency(?string $requested, array $companyIds = []): string
    {
        if ($requested !== null && $requested !== '') {
            return strtoupper($requested);
        }

        if (!empty($companyIds) && Schema::hasTable('companies') && Schema::hasColumn('companies', 'currency')) {
            $currency = Company::query()
                ->whereIn('id', $companyIds)
                ->value('currency');

            if (!empty($currency)) {
                return strtoupper($currency);
            }
        }

        return 'PHP';
    }

    /**
     * Aggregate annual revenue via payments when requested.
     *
     * @param Builder $bookingsQuery
     * @param string  $rangeStart
     * @param string  $rangeEnd
     *
     * @return float
     */
    protected function aggregatePaymentsRevenue(Builder $bookingsQuery, string $rangeStart, string $rangeEnd): float
    {
        $bookingIdsSubQuery = (clone $bookingsQuery)->select('bookings.id');

        return (float) Payment::query()
            ->whereIn('booking_id', $bookingIdsSubQuery)
            ->where('status', 'Paid')
            ->whereBetween('paid_at', [$rangeStart, $rangeEnd])
            ->sum('amount');
    }

    /**
     * Apply the date range constraint respecting configured fallback.
     *
     * @param Builder     $query
     * @param string      $dateColumn
     * @param string|null $dateFallback
     * @param string      $rangeStart
     * @param string      $rangeEnd
     */
    protected function applyDateRangeConstraint(
        Builder $query,
        string $dateColumn,
        ?string $dateFallback,
        string $rangeStart,
        string $rangeEnd
    ): void {
        if ($dateFallback !== null) {
            $expression = DB::raw(sprintf('COALESCE(%s, %s)', $dateColumn, $dateFallback));
            $query->whereBetween($expression, [$rangeStart, $rangeEnd]);
        } else {
            $query->whereBetween($dateColumn, [$rangeStart, $rangeEnd]);
        }
    }

    /**
     * Resolve the tenant's accessible companies, validating explicit selections.
     *
     * @param User    $tenant
     * @param int|null $requestedCompanyId
     *
     * @return array<int>
     *
     * @throws AuthorizationException
     */
    protected function resolveCompanyScope(User $tenant, ?int $requestedCompanyId): array
    {
        $accessible = Company::query()
            ->where('user_id', $tenant->getKey())
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        if ($requestedCompanyId === null) {
            return $accessible;
        }

        if (!in_array($requestedCompanyId, $accessible, true)) {
            throw new AuthorizationException('You are not allowed to access the requested company.');
        }

        return [$requestedCompanyId];
    }

    /**
     * Normalise incoming filter values.
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    protected function normaliseFilters(array $filters): array
    {
        $now = now('Asia/Manila');
        $year = (int) ($filters['year'] ?? $now->year);

        $statusInput = $filters['status'] ?? $filters['statuses'] ?? [];
        if (is_string($statusInput)) {
            $statusInput = array_map('trim', explode(',', $statusInput));
        }

        $statuses = collect($statusInput)
            ->filter(static fn ($value) => $value !== null && $value !== '')
            ->map(function ($value) {
                $normalised = ucfirst(Str::lower((string) $value));
                return $normalised;
            })
            ->filter(function ($value) {
                if (in_array($value, self::EXCLUDED_STATUSES, true)) {
                    return false;
                }
                if (empty(self::ALLOWED_STATUSES)) {
                    return true;
                }

                return in_array($value, self::ALLOWED_STATUSES, true);
            })
            ->unique()
            ->values()
            ->all();

        if (empty($statuses)) {
            $statuses = array_values(array_diff(self::DEFAULT_STATUSES, self::EXCLUDED_STATUSES));
        }

        $dateFieldKey = Str::snake((string) ($filters['date_field'] ?? 'actual_return'));
        $dateFieldConfig = self::DATE_FIELD_MAP[$dateFieldKey] ?? self::DATE_FIELD_MAP['actual_return'];

        $usePayments = filter_var(
            Arr::get($filters, 'use_payments', false),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        $preset = Str::snake((string) ($filters['preset'] ?? 'year_to_date'));
        if (!in_array($preset, self::PRESETS, true)) {
            $preset = 'year_to_date';
        }

        $includeTrend = filter_var(
            Arr::get($filters, 'include_trend', false),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        $customStart = $preset === 'custom' ? Arr::get($filters, 'start_date') : null;
        $customEnd = $preset === 'custom' ? Arr::get($filters, 'end_date') : null;

        return [
            'year'          => $year,
            'company_id'    => Arr::has($filters, 'company_id') ? (int) Arr::get($filters, 'company_id') : null,
            'car_id'        => Arr::has($filters, 'car_id') ? (int) Arr::get($filters, 'car_id') : null,
            'statuses'      => $statuses,
            'date_column'   => $dateFieldConfig['column'],
            'date_fallback' => $dateFieldConfig['fallback'] ?? null,
            'use_payments'  => (bool) $usePayments,
            'preset'        => $preset,
            'custom_start'  => $customStart,
            'custom_end'    => $customEnd,
            'include_trend' => (bool) $includeTrend,
            'currency'      => Arr::get($filters, 'currency'),
        ];
    }

    /**
     * Normalise incoming filter values for fleet utilisation requests.
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    protected function normaliseUtilizationFilters(array $filters): array
    {
        $base = $this->normaliseFilters($filters);

        $timezone = $this->resolveTimezone(Arr::get($filters, 'timezone'));
        $granularity = Str::lower((string) Arr::get($filters, 'granularity', 'day'));
        if (!in_array($granularity, ['day', 'hour'], true)) {
            $granularity = 'day';
        }

        $now = now('Asia/Manila');
        [$rangeStart, $rangeEnd] = $this->resolveDateRange(
            (int) $base['year'],
            $base['preset'],
            $base['custom_start'],
            $base['custom_end'],
            $now
        );

        $asOf = $now->copy();
        if ($asOf->gt($rangeEnd)) {
            $asOf = $rangeEnd->copy();
        }
        if ($asOf->lt($rangeStart)) {
            $asOf = $rangeStart->copy();
        }

        $base['range_start'] = $rangeStart;
        $base['range_end'] = $rangeEnd;
        $base['as_of'] = $asOf;
        $base['response_timezone'] = $timezone;
        $base['granularity'] = $granularity;
        $base['statuses'] = $base['statuses'] ?? $this->defaultStatuses();

        return $base;
    }

    /**
     * Convert a year into a [start, end] range bound inside Asia/Manila timezone.
     *
     * @param int $year
     *
     * @return array{0:string,1:string}
     */
    protected function makeYearRange(int $year): array
    {
        $start = Carbon::createFromDate($year, 1, 1, 'Asia/Manila')->startOfDay();
        $end = (clone $start)->endOfYear()->endOfDay();

        return [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];
    }

    /**
     * Produce a consistent cache key.
     *
     * @param int                $tenantId
     * @param array<string,mixed> $filters
     *
     * @return string
     */
    protected function makeCacheKey(int $tenantId, array $filters): string
    {
        ksort($filters);

        return sprintf('tenant-dashboard:%d:%s', $tenantId, md5(json_encode($filters)));
    }

    /**
     * Produce a cache key for fleet utilisation responses.
     *
     * @param int                $tenantId
     * @param array<string,mixed> $filters
     */
    protected function makeUtilizationCacheKey(int $tenantId, array $filters): string
    {
        $payload = [
            'company_id'   => $filters['company_id'] ?? null,
            'preset'       => $filters['preset'] ?? null,
            'custom_start' => $filters['custom_start'] ?? null,
            'custom_end'   => $filters['custom_end'] ?? null,
            'granularity'  => $filters['granularity'] ?? 'day',
            'includeTrend' => $filters['include_trend'] ?? false,
            'range_start'  => $filters['range_start'] instanceof Carbon ? $filters['range_start']->format('Y-m-d H:i:s') : null,
            'range_end'    => $filters['range_end'] instanceof Carbon ? $filters['range_end']->format('Y-m-d H:i:s') : null,
            'as_of'        => $filters['as_of'] instanceof Carbon ? $filters['as_of']->format('Y-m-d H:i:s') : null,
            'timezone'     => $filters['response_timezone'] ?? null,
            'statuses'     => $filters['statuses'] ?? [],
        ];

        $payload['statuses'] = is_array($payload['statuses']) ? array_values($payload['statuses']) : [];

        ksort($payload);

        return sprintf('tenant-fleet-util:%d:%s', $tenantId, md5(json_encode($payload)));
    }

    /**
     * Round money values to two decimal places.
     */
    protected function roundMoney(float $value): float
    {
        return round($value, 2);
    }

    /**
     * Resolve a timezone string, falling back to the application default when invalid.
     */
    protected function resolveTimezone(?string $timezone): string
    {
        $default = config('app.timezone', 'UTC');

        if (!$timezone) {
            return $default;
        }

        try {
            new \DateTimeZone($timezone);
            return $timezone;
        } catch (\Exception $exception) {
            return $default;
        }
    }

    /**
     * Compose an empty summary payload.
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    protected function emptySummary(array $filters, Carbon $rangeStart, Carbon $rangeEnd, string $currency): array
    {
        $generatedAt = now('Asia/Manila')->toIso8601String();

        $response = [
            'period' => [
                'year'     => (int) $filters['year'],
                'currency' => $currency,
            ],
            'resolvedRange' => [
                'start' => $rangeStart->toIso8601String(),
                'end'   => $rangeEnd->toIso8601String(),
            ],
            'totals' => [
                'annualRevenue'      => 0.0,
                'bookingsYtd'        => 0,
                'averageBookingValue'=> 0.0,
            ],
            'meta' => [
                'source'          => $filters['use_payments'] ? 'payments' : 'bookings',
                'statusesCounted' => $filters['statuses'],
                'generatedAt'     => $generatedAt,
            ],
        ];

        if ($filters['include_trend']) {
            $response['trend'] = [
                'previous' => [
                    'annualRevenue' => 0.0,
                    'bookingsYtd' => 0,
                    'averageBookingValue' => 0.0,
                    'period' => null,
                ],
                'percentChange' => [
                    'annualRevenue' => null,
                    'bookingsYtd' => null,
                    'averageBookingValue' => null,
                ],
            ];
        }

        return $response;
    }

    /**
     * Default booking statuses used for utilisation counting.
     *
     * @return array<int, string>
     */
    protected function defaultStatuses(): array
    {
        return array_values(array_diff(self::DEFAULT_STATUSES, self::EXCLUDED_STATUSES));
    }
}
