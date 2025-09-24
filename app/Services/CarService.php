<?php

namespace App\Services;

use App\Repositories\Contracts\CarRepositoryInterface;
use App\Services\Contracts\CarServiceInterface;
use App\Http\Resources\Car\CarResource;

class CarService implements CarServiceInterface
{
    protected CarRepositoryInterface $carRepository;

    /**
     * CarService constructor.
     *
     * @param CarRepositoryInterface $carRepository
     */
    public function __construct(CarRepositoryInterface $carRepository)
    {
        $this->carRepository = $carRepository;
    }

    /**
     * List cars with pagination, filters, and sorting.
     *
     * @param array $filters
     * @param array $order
     * @param int $limit
     * @param int $page
     * @param array $includes
     * 
     * @return array
    */
    public function getList(array $filters = [], array $order = [], int $limit = 10, int $page = 1, array $includes = []): array
    {
        return CarResource::collection(
            $this->carRepository->listing($filters, $order, $limit, $page, $includes)
        )->response()->getData(true);
    }

    /**
     * Get details of a car.
     *
     * @param int $id
     * @return array
     */
    public function detail(int $id): array
    {
        return (new CarResource(
            $this->carRepository->findById($id)
        ))->response()->getData(true);
    }

    /**
     * Register a new car.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
        $car = $this->carRepository->create($data);
        return (new CarResource($car))->response()->getData(true);
    }

    /**
     * Update an existing car.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        return $this->carRepository->update($id, $data);
    }

    /**
     * Delete a car by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->carRepository->delete($id);
    }
}
