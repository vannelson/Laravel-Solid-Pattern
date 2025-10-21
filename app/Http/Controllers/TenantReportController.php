<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\MonthlySalesRequest;
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

        // try {
            $payload = $this->reports->getMonthlySales($tenant, $request->validated());

            return response()->json($payload);
        // } catch (AuthorizationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 403);
        // } catch (Throwable $exception) {
        //     report($exception);

        //     return response()->json([
        //         'message' => 'Failed to load monthly sales report.',
        //     ], 500);
        // }
    }
}
