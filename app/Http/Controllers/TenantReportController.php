<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\HighlightsRequest;
use App\Http\Requests\Dashboard\MonthlySalesRequest;
use App\Http\Requests\Dashboard\RevenueByClassRequest;
use App\Http\Requests\Dashboard\UtilizationSnapshotRequest;
use App\Http\Requests\Dashboard\UpcomingBookingsRequest;
use App\Http\Requests\Dashboard\TopPerformersRequest;
use App\Services\Contracts\TenantReportServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Throwable;

class TenantReportController extends Controller
{
    protected TenantReportServiceInterface $reports;

    public function __construct(TenantReportServiceInterface $reports)
    {
        $this->reports = $reports;
    }

    /**
     * Serve the monthly sales + trend dataset for dashboard charts.
     */
    public function monthlySales(MonthlySalesRequest $request): JsonResponse
    {
        $tenant = $request->user();

        try {
            $payload = $this->reports->getMonthlySales($tenant, $request->validated());

            return response()->json($payload);
        } catch (AuthorizationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 403);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Failed to load monthly sales report.',
            ], 500);
        }
    }

    /**
     * Deliver the hero-card highlight metrics.
     */
    public function highlights(HighlightsRequest $request): JsonResponse
    {
        $tenant = $request->user();

        try {
            $payload = $this->reports->getHighlights($tenant, $request->validated());

            return response()->json($payload);
        } catch (AuthorizationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 403);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Failed to load dashboard highlights.',
            ], 500);
        }
    }

    /**
     * Return revenue distribution by car classification.
     */
    public function revenueByClass(RevenueByClassRequest $request): JsonResponse
    {
        $tenant = $request->user();

        try {
            $payload = $this->reports->getRevenueByClass($tenant, $request->validated());

            return response()->json($payload);
        } catch (AuthorizationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 403);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Failed to load revenue by class.',
            ], 500);
        }
    }

    /**
     * Provide the live utilisation snapshot.
     */
    public function utilization(UtilizationSnapshotRequest $request): JsonResponse
    {
        $tenant = $request->user();

        try {
            $payload = $this->reports->getUtilizationSnapshot($tenant, $request->validated());

            return response()->json($payload);
        } catch (AuthorizationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 403);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Failed to load utilisation snapshot.',
            ], 500);
        }
    }

    /**
     * Provide the upcoming bookings schedule.
     */
    public function upcomingBookings(UpcomingBookingsRequest $request): JsonResponse
    {
        $tenant = $request->user();

        try {
            $payload = $this->reports->getUpcomingBookings($tenant, $request->validated());

            return response()->json($payload);
        } catch (AuthorizationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 403);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Failed to load upcoming bookings.',
            ], 500);
        }
    }

    /**
     * Surface top-performing vehicles for the dashboard.
     */
    public function topPerformers(TopPerformersRequest $request): JsonResponse
    {
        $tenant = $request->user();

        try {
            $payload = $this->reports->getTopPerformers($tenant, $request->validated());

            return response()->json($payload);
        } catch (AuthorizationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 403);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Failed to load top performers.',
            ], 500);
        }
    }
}
