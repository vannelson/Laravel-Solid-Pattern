<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\CompanyStoreRequest;
use App\Http\Requests\Company\CompanyUpdateRequest;
use App\Services\Contracts\CompanyServiceInterface;
use App\Http\Resources\Company\CompanyResource;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
class CompanyController extends Controller
{
    use ResponseTrait;

    protected CompanyServiceInterface $companyService;

    public function __construct(CompanyServiceInterface $companyService)
    {
        $this->companyService = $companyService;
    }

    /**
     * Display a listing of companies (static data).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = Arr::get($request->all(), 'filters', []);
        $order   = Arr::get($request->all(), 'order', ['id', 'desc']);
        $limit   = (int) Arr::get($request->all(), 'limit', 10);
        $page    = (int) Arr::get($request->all(), 'page', 1);
    
        try {
            $data = $this->companyService->getList($filters, $order, $limit, $page);
            return $this->successPagination('Companies retrieved successfully!', $data);
        } catch (\Exception $e) {
            return $this->error('Failed to load song.', 500);
        }
    }

    /**
     * Store a new company (static).
     *
     * @param CompanyStoreRequest $request
     * @return JsonResponse
     */
    public function store(CompanyStoreRequest $request): JsonResponse
    {
       try {
        $company = $this->companyService->register($request->validated());
        return $this->success('Company registered successfully!', $company);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            return $this->error('Failed to register .', 500);
        }
    }

    /**
     * Show a specific company (static).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
         $user = $this->companyService->detail($id);
        return $this->success('User registered successfully!', $user);
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
        try {
            $this->companyService->update($id, $request->validated());
            return $this->success('Company updated successfully!');
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            return $this->error('Failed to update user.', 500);
        }
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
