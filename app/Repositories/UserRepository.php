<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * UserRepository constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        parent::__construct($user);
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
        
        if ($type = Arr::get($filters, 'type')) {
            $query->where('type', $type);
        }

        // Apply optional filters
        if ($first = Arr::get($filters, 'first_name')) {
            $query->where('first_name', 'LIKE', "%$first%");
        }
        if ($middle = Arr::get($filters, 'middle_name')) {
            $query->where('middle_name', 'LIKE', "%$middle%");
        }
        if ($last = Arr::get($filters, 'last_name')) {
            $query->where('last_name', 'LIKE', "%$last%");
        }
        // Full name search using CONCAT_WS for accurate spacing and NULL handling
        if ($name = Arr::get($filters, 'name')) {
            $kw = "%{$name}%";
            $query->whereRaw("CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ?", [$kw]);
        }

        if ($email = Arr::get($filters, 'email')) {
            $query->where('email', 'LIKE', "%$email%");
        }

        // Apply ordering (default: id desc)
        [$orderBy, $dir] = !empty($order) ? $order : ['id', 'desc'];
        $query->orderBy($orderBy, $dir);

        // Return paginator instance
        return $query->paginate($limit, ['*'], 'page', $page);
    }
}
