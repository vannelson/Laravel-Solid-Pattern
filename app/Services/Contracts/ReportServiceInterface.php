<?php

namespace App\Services\Contracts;

interface ReportServiceInterface
{
    public function getKpis(int $year, ?int $tenantId = null): array;

    public function getRevenueTrend(int $year, ?int $tenantId = null, ?int $comparisonYear = null): array;

    public function getRevenueByClass(int $year, ?int $tenantId = null): array;

    public function getTopVehicles(int $year, ?int $tenantId = null, int $limit = 4): array;

    public function getUpcomingBookings(string $range, ?int $tenantId = null): array;

    public function getActivityFeed(int $limit = 5, ?int $tenantId = null): array;

    public function getFleetSnapshot(?int $tenantId = null): array;
}

