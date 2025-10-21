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
}
