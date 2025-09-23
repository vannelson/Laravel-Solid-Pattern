<?php

namespace App\Repositories\Contracts;
use Illuminate\Pagination\LengthAwarePaginator;


interface CompanyRepositoryInterface
{
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
