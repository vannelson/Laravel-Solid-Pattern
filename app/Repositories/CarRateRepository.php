<?php

namespace App\Repositories;

use App\Models\CarRate;
use App\Repositories\Contracts\CarRateRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class CarRateRepository extends BaseRepository implements CarRateRepositoryInterface
{
    public function __construct(CarRate $carRate)
    {
        parent::__construct($carRate);
    }

    /**
     * List car rates with pagination, filters, and sorting.
     *
     * @param array $filters
     * @param array $order
     * @param int $limit
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function listing(array $filters = [], array $order = [], int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if ($name = Arr::get($filters, 'name')) {
            $query->where('name', 'LIKE', "%$name%");
        }

        if ($carId = Arr::get($filters, 'car_id')) {
            $query->where('car_id', $carId);
        }

        if ($status = Arr::get($filters, 'status')) {
            $query->where('status', $status);
        }

        [$orderBy, $dir] = !empty($order) ? $order : ['id', 'desc'];
        $query->orderBy($orderBy, $dir);

        return $query->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Update a car rate and enforce single active rate per car when activating.
     *
     * @param int $id
     * @param array $data
     * @return int
     */
    public function update(int $id, array $data): int
    {
        // If status is being set to active, deactivate other active rates for the same car
        if (array_key_exists('status', $data) && $data['status'] === 'active') {
            $current = $this->model->findOrFail($id);

            $this->model->where('car_id', $current->car_id)
                ->where('id', '!=', $id)
                ->where('status', 'active')
                ->update(['status' => 'inactive']);
        }

        return parent::update($id, $data);
    }
}
