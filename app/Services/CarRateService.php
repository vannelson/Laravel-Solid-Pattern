<?php

namespace App\Services;

use App\Repositories\Contracts\CarRateRepositoryInterface;
use App\Services\Contracts\CarRateServiceInterface;
use App\Http\Resources\CarRate\CarRateResource;

class CarRateService implements CarRateServiceInterface
{
    protected CarRateRepositoryInterface $carRateRepository;

    public function __construct(CarRateRepositoryInterface $carRateRepository)
    {
        $this->carRateRepository = $carRateRepository;
    }

    /**
     * List car rates with pagination, filters, and sorting.
     */
    public function getList(array $filters = [], array $order = [], int $limit = 10, int $page = 1): array
    {
        return CarRateResource::collection(
            $this->carRateRepository->listing($filters, $order, $limit, $page)
        )->response()->getData(true);
    }

    /**
     * Get details of a car rate by ID.
     */
    public function detail($id): array
    {
        return (new CarRateResource(
            $this->carRateRepository->findById($id)
        ))->response()->getData(true);
    }

    /**
     * Create a new car rate.
     */
    public function register(array $data): array
    {
        $rate = $this->carRateRepository->create($data);
        return (new CarRateResource($rate))->response()->getData(true);
    }

    /**
     * Update an existing car rate.
     */
    public function update(int $id, array $data): bool
    {
        return $this->carRateRepository->update($id, $data);
    }

    /**
     * Delete a car rate by ID.
     */
    public function delete(int $id): bool
    {
        return $this->carRateRepository->delete($id);
    }
}
