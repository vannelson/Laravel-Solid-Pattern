<?php

namespace App\Repositories;

use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

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
}
