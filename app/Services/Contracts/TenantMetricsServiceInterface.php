<?php

namespace App\Services\Contracts;

use App\Models\User;

interface TenantMetricsServiceInterface
{
    /**
     * Build a cached dashboard summary for the authenticated tenant.
     *
     * @param User  $tenant  The authenticated tenant user.
     * @param array $filters Request filters (year, company, car, status, etc.).
     *
     * @return array<string, mixed>
     */
    public function getDashboardSummary(User $tenant, array $filters = []): array;

    /**
     * Build the fleet utilisation payload for the authenticated tenant.
     *
     * @param User  $tenant  The authenticated tenant user.
     * @param array $filters Request filters (company, preset, dates, timezone, granularity).
     *
     * @return array<string, mixed>
     */
    public function getFleetUtilization(User $tenant, array $filters = []): array;
}
