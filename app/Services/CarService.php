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
     * {@inheritdoc}
     */
    public function getList(array $filters = [], array $order = [], int $limit = 10, int $page = 1): array
    {
        return CarResource::collection(
            $this->carRepository->listing($filters, $order, $limit, $page)
        )->response()->getData(true);
    }

    /**
     * {@inheritdoc}
     */
    public function detail(int $id): array
    {
        return (new CarResource(
            $this->carRepository->findById($id)
        ))->response()->getData(true);
    }

    /**
     * {@inheritdoc}
     */
    public function register(array $data): array
    {
        $car = $this->carRepository->create($data);
        return (new CarResource($car))->response()->getData(true);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): bool
    {
        return $this->carRepository->update($id, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        return $this->carRepository->delete($id);
    }
}
