<?php

namespace App\Repositories;

use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class CompanyRepository extends BaseRepository implements CompanyRepositoryInterface
{
    /**
     * UserRepository constructor.
     *
     * @param User $user
     */
    public function __construct(Company $company)
    {
        parent::__construct($company);
    }

    /**
     * Retrieve a paginated list of songs 
     *
     * @param array $filters 
     * @param array $order 
     * @param int $limi
     * @param int $page 
     * @return LengthAwarePaginator 
     */
    public function listing(array $filters = [], array $order = [], int $limit = 10, int $page = 1): LengthAwarePaginator
    {
         $query = $this->model->newQuery();
        // eager load user
        $query->with('user');
        // Primary filter: owner
        if ($userId = Arr::get($filters, 'user_id')) {
            $query->where('user_id', $userId);
        }

        // Apply optional filters
        if ($name = Arr::get($filters, 'name')) {
            $query->where('name', 'LIKE', "%$name%");
        }

        if ($address = Arr::get($filters, 'address')) {
            $query->where('address', 'LIKE', "%$address%");
        }

        if (($latitude = Arr::get($filters, 'latitude')) !== null) {
            $query->where('latitude', $latitude);
        }

        if (($longitude = Arr::get($filters, 'longitude')) !== null) {
            $query->where('longitude', $longitude);
        }

        // Apply ordering (default: id desc)
        [$orderBy, $dir] = !empty($order) ? $order : ['id', 'desc'];
        $query->orderBy($orderBy, $dir);

        // Return paginator instance
        return $query->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Ensure only one default company per user.
     */
    public function clearDefaultForUser(int $userId, ?int $exceptId = null): void
    {
        $query = $this->model->newQuery()->where('user_id', $userId);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update(['is_default' => false]);
    }

    /**
     * {@inheritDoc}
     */
    public function findNearby(float $latitude, float $longitude, int $radiusMeters, array $options = []): Collection
    {
        $limit = (int) ($options['limit'] ?? 20);
        $limit = max(1, min($limit, 100));
        $withCars = (bool) ($options['with_cars'] ?? false);
        $requireAvailableCars = array_key_exists('require_available_cars', $options)
            ? (bool) $options['require_available_cars']
            : true;
        $filters = Arr::get($options, 'filters', []);
        $minDistance = Arr::get($options, 'min_distance');
        $minDistance = $minDistance !== null ? (int) $minDistance : null;
        $carType = Arr::get($filters, 'car_type');

        $radiusMeters = max(1, $radiusMeters);

        $earthRadius = 6371000;
        $distanceFormula = "{$earthRadius} * acos(least(1, greatest(-1, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))))";

        $query = $this->model->newQuery()
            ->select('companies.*')
            ->selectRaw("({$distanceFormula}) as distance_m", [$latitude, $longitude, $latitude])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($industry = Arr::get($filters, 'industry')) {
            $query->where('industry', 'LIKE', '%' . $industry . '%');
        }

        if ($name = Arr::get($filters, 'name')) {
            $query->where('name', 'LIKE', '%' . $name . '%');
        }

        if ($requireAvailableCars || $carType) {
            $query->whereExists(function ($carQuery) use ($carType) {
                $carQuery->selectRaw('1')
                    ->from('cars')
                    ->whereColumn('cars.company_id', 'companies.id')
                    ->whereRaw("LOWER(cars.info_availabilityStatus) = 'available'");

                if ($carType) {
                    $carQuery->where('cars.info_carType', $carType);
                }
            });
        }

        if ($minDistance !== null) {
            $query->havingRaw('distance_m >= ?', [$minDistance]);
        }

        $query->havingRaw('distance_m <= ?', [$radiusMeters])
            ->orderBy('distance_m')
            ->limit($limit);

        if ($withCars) {
            $query->with(['cars' => function ($relation) use ($carType) {
                $relation->select([
                        'id',
                        'company_id',
                        'info_make',
                        'info_model',
                        'info_carType',
                        'info_availabilityStatus',
                        'info_location',
                    ])
                    ->whereRaw("LOWER(info_availabilityStatus) = 'available'")
                    ->orderBy('id');

                if ($carType) {
                    $relation->where('info_carType', $carType);
                }

                $relation->with(['activeRate' => function ($rateQuery) {
                    $rateQuery->select('id', 'car_id', 'name', 'rate', 'rate_type', 'status')
                        ->where('status', 'active');
                }]);
            }]);
        }

        return $query->get();
    }
}
