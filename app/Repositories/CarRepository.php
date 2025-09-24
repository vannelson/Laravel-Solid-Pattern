<?php

namespace App\Repositories;

use App\Models\Car;
use App\Repositories\Contracts\CarRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class CarRepository extends BaseRepository implements CarRepositoryInterface
{
    /**
     * CarRepository constructor.
     *
     * @param Car $car
     */
    public function __construct(Car $car)
    {
        parent::__construct($car);
    }

    /**
     * List cars with pagination, filters, and sorting.
     *
     * @param array $filters
     * @param array $order
     * @param int $limit
     * @param int $page
     * @param array $includes
     * @return LengthAwarePaginator
     */
    public function listing(array $filters = [], array $order = [], int $limit = 10, int $page = 1, array $includes = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // 👇 Load relationships if requested
        if (!empty($includes)) {
            $query->with($includes);
        }

        // Filters
        if ($make = Arr::get($filters, 'info_make')) {
            $query->where('info_make', 'LIKE', "%$make%");
        }
        if ($model = Arr::get($filters, 'info_model')) {
            $query->where('info_model', 'LIKE', "%$model%");
        }
        if ($year = Arr::get($filters, 'info_year')) {
            $query->where('info_year', $year);
        }

        // Ordering
        [$orderBy, $dir] = !empty($order) ? $order : ['id', 'desc'];
        $query->orderBy($orderBy, $dir);

        return $query->paginate($limit, ['*'], 'page', $page);
    }
}
