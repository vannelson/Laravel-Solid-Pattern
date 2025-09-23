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
     * Find a user by email.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email)
    {
        return $this->model->where('email', $email)->first();
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

        // Apply optional filters
        if ($name = Arr::get($filters, 'name')) {
            $query->where('name', 'LIKE', "%$name%");
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
