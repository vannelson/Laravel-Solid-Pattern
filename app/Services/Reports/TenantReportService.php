<?php

namespace App\Services\Reports;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Services\Concerns\ResolvesTenantScope;
use App\Services\Contracts\TenantReportServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class TenantReportService implements TenantReportServiceInterface
{
    use ResolvesTenantScope;

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
