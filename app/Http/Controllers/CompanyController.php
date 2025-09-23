<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\CompanyStoreRequest;
use App\Http\Requests\Company\CompanyUpdateRequest;
use App\Http\Resources\Company\CompanyResource;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    use ResponseTrait;

    /**
     * Display a listing of companies (static data).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([]);
    }

    /**
     * Store a new company (static).
     *
     * @param CompanyStoreRequest $request
     * @return JsonResponse
     */
    public function store(CompanyStoreRequest $request): JsonResponse
    {
        return response()->json([]);
    }

    /**
     * Show a specific company (static).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        return response()->json([]);
    }

    /**
     * Update an existing company (static).
     *
     * @param CompanyUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(CompanyUpdateRequest $request, int $id): JsonResponse
    {
        return response()->json([]);
    }

    /**
     * Delete a company (static).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        return response()->json([]);
    }
}
