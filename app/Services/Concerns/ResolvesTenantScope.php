<?php

namespace App\Services\Concerns;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Schema;

trait ResolvesTenantScope
{
    /**
     * Resolve the tenant's accessible companies, validating explicit selections.
     *
     * @throws AuthorizationException
     *
     * @return array<int>
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
     * Resolve the currency to display for the given company scope.
     */
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
}
