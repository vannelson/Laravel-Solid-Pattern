<?php

namespace App\Services\Contracts;

use App\Models\User;

interface TenantReportServiceInterface
{
    /**
     * Produce monthly sales metrics for the tenant within the supplied window.
     *
     * @param User  $tenant
     * @param array $filters
     *
     * @return array<string, mixed>
     */
    public function getMonthlySales(User $tenant, array $filters = []): array;

    /**
     * Fetch consolidated highlight cards for the dashboard.
     */
    public function getHighlights(User $tenant, array $filters = []): array;

    /**
     * Aggregate revenue by car classification.
     */
    public function getRevenueByClass(User $tenant, array $filters = []): array;

    /**
     * Capture the live utilisation snapshot for the tenant.
     */
    public function getUtilizationSnapshot(User $tenant, array $filters = []): array;

    /**
     * Retrieve upcoming bookings within a date window.
     */
    public function getUpcomingBookings(User $tenant, array $filters = []): array;

    /**
     * Surface top-performing vehicles in the selected window.
     */
    public function getTopPerformers(User $tenant, array $filters = []): array;
}
