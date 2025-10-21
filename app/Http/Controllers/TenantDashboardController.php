<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\DashboardSummaryRequest;
use App\Http\Requests\Dashboard\FleetUtilizationRequest;
use App\Services\Contracts\TenantMetricsServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Throwable;

class TenantDashboardController extends Controller
{
    protected TenantMetricsServiceInterface $metrics;

    public function __construct(TenantMetricsServiceInterface $metrics)
    {
        $this->metrics = $metrics;
    }

    /**
     * Handle the dashboard summary endpoint.
     */
    public function summary(DashboardSummaryRequest $request): JsonResponse
    {
        $tenant = $request->user();

        try {
            $summary = $this->metrics->getDashboardSummary($tenant, $request->validated());

            return response()->json($summary);
        } catch (AuthorizationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 403);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Failed to load dashboard summary.',
            ], 500);
        }
    }

    /**
     * Handle the fleet utilisation endpoint backing the live gauge.
     */
    public function fleetUtilization(FleetUtilizationRequest $request): JsonResponse
    {
        $tenant = $request->user();

        try {
            $payload = $this->metrics->getFleetUtilization($tenant, $request->validated());

            return response()->json($payload);
        } catch (AuthorizationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 403);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Failed to load fleet utilisation metrics.',
            ], 500);
        }
    }
}
