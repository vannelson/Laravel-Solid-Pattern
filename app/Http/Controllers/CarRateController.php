<?php

namespace App\Http\Controllers;

use App\Http\Requests\CarRate\CarRateStoreRequest;
use App\Http\Requests\CarRate\CarRateUpdateRequest;
use App\Services\Contracts\CarRateServiceInterface;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class CarRateController extends Controller
{
    use ResponseTrait;

    protected CarRateServiceInterface $carRateService;

    public function __construct(CarRateServiceInterface $carRateService)
    {
        $this->carRateService = $carRateService;
    }

    /**
     * Display a listing of car rates.
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
            $data = $this->carRateService->getList($filters, $order, $limit, $page);
            return $this->successPagination('Car rates retrieved successfully!', $data);
        } catch (\Exception $e) {
            return $this->error('Failed to load car rates.', 500);
        }
    }

    /**
     * Store a new car rate.
     *
     * @param CarRateStoreRequest $request
     * @return JsonResponse
     */
    public function store(CarRateStoreRequest $request): JsonResponse
    {
        try {
            $rate = $this->carRateService->register($request->validated());
            return $this->success('Car rate created successfully!', $rate);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            return $this->error('Failed to create car rate.', 500);
        }
    }

    /**
     * Display a specific car rate.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $rate = $this->carRateService->detail($id);
        return $this->success('Car rate retrieved successfully!', $rate);
    }

    /**
     * Update a car rate.
     *
     * @param CarRateUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(CarRateUpdateRequest $request, int $id): JsonResponse
    {
        try {
            $this->carRateService->update($id, $request->validated());
            return $this->success('Car rate updated successfully!');
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            return $this->error('Failed to update car rate.', 500);
        }
    }

    /**
     * Delete a car rate.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->carRateService->delete($id);
            return $this->success('Car rate deleted successfully!');
        } catch (\Exception $e) {
            return $this->error('Failed to delete car rate.', 500);
        }
    }
}
