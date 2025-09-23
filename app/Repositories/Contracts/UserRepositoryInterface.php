<?php

namespace App\Repositories\Contracts;
use Illuminate\Pagination\LengthAwarePaginator;


interface UserRepositoryInterface
{
    /**
     * Find a user by email.
     *
     * @param string $email
     * @return mixed
     */
    public function findByEmail(string $email);

    /**
     * Find a user by ID.
     *
     * @param int $id
     * @return mixed
     */
    public function findById(int $id);

    /**
     * Get Users List 
     * 
     * @param array $filters
     * @param array $order 
     * @param int $limit 
     * @param int $page 
    */
    public function listing(array $filters = [], array $order = [], int $limit = 10, int $page = 1): LengthAwarePaginator;
}
