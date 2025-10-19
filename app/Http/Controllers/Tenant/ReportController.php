<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\Contracts\ReportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected ReportServiceInterface $reports;

    public function __construct(ReportServiceInterface $reports)
    {
        $this->reports = $reports;
    }

    public function kpis(Request $request): JsonResponse
    {
        $year = (int) $request->query('year', now()->year);
        $tenantId = $this->extractTenantId($request);

        return response()->json(
            $this->reports->getKpis($year, $tenantId)
        );
    }

    public function revenueTrend(Request $request): JsonResponse
    {
        $year = (int) $request->query('year', now()->year);
        $compareYear = $request->query('compareYear');
        $tenantId = $this->extractTenantId($request);

        $comparisonYear = $compareYear !== null ? (int) $compareYear : null;

        return response()->json(
            $this->reports->getRevenueTrend($year, $tenantId, $comparisonYear)
        );
    }

    public function revenueByClass(Request $request): JsonResponse
    {
        $year = (int) $request->query('year', now()->year);
        $tenantId = $this->extractTenantId($request);

        return response()->json(
            $this->reports->getRevenueByClass($year, $tenantId)
        );
    }

    public function topVehicles(Request $request): JsonResponse
    {
        $period = $request->query('period', now()->year);
        $year = is_numeric($period) ? (int) $period : now()->year;
        $limit = (int) $request->query('limit', 4);
        $tenantId = $this->extractTenantId($request);

        return response()->json(
            $this->reports->getTopVehicles($year, $tenantId, $limit)
        );
    }

    public function upcomingBookings(Request $request): JsonResponse
    {
        $range = $request->query('range', 'next-5-days');
        $tenantId = $this->extractTenantId($request);

        return response()->json(
            $this->reports->getUpcomingBookings($range, $tenantId)
        );
    }

    public function activityFeed(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 5);
        $tenantId = $this->extractTenantId($request);

        return response()->json(
            $this->reports->getActivityFeed($limit, $tenantId)
        );
    }

    public function fleetSnapshot(Request $request): JsonResponse
    {
        $tenantId = $this->extractTenantId($request);

        return response()->json(
            $this->reports->getFleetSnapshot($tenantId)
        );
    }

    protected function extractTenantId(Request $request): ?int
    {
        $tenantId = $request->query('tenant_id');
        if ($tenantId === null) {
            return null;
        }

        return is_numeric($tenantId) ? (int) $tenantId : null;
    }
}

