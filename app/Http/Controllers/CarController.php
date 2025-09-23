<?php

namespace App\Http\Controllers;

use App\Http\Requests\Car\CarStoreRequest;
use App\Http\Requests\Car\CarUpdateRequest;
use App\Services\Contracts\CarServiceInterface;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class CarController extends Controller
{
    use ResponseTrait;

    protected CarServiceInterface $carService;

    /**
     * CarController constructor.
     *
     * @param CarServiceInterface $carService
     */
    public function __construct(CarServiceInterface $carService)
    {
        $this->carService = $carService;
    }

    /**
     * Display a listing of cars.
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
            $data = $this->carService->getList($filters, $order, $limit, $page);
            return $this->successPagination('Cars retrieved successfully!', $data);
        } catch (\Exception $e) {
            return $this->error('Failed to load cars.', 500);
        }
    }

    /**
     * Store a new car.
     *
     * @param CarStoreRequest $request
     * @return JsonResponse
     */
    public function store(CarStoreRequest $request): JsonResponse
    {
        try {
            $car = $this->carService->register($request->validated());
            return $this->success('Car registered successfully!', $car);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            return $this->error('Failed to register car.', 500);
        }
    }

    /**
     * Display a specific car.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $car = $this->carService->detail($id);
            return $this->success('Car retrieved successfully!', $car);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve car.', 500);
        }
    }

    /**
     * Update an existing car.
     *
     * @param CarUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(CarUpdateRequest $request, int $id): JsonResponse
    {
        try {
            $this->carService->update($id, $request->validated());
            return $this->success('Car updated successfully!');
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            return $this->error('Failed to update car.', 500);
        }
    }

    /**
     * Remove a car.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->carService->delete($id);
            return $this->success('Car deleted successfully!');
        } catch (\Exception $e) {
            return $this->error('Failed to delete car.', 500);
        }
    }
}
