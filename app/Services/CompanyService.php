<?php

namespace App\Services;

use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Services\Contracts\CompanyServiceInterface;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\Company\CompanyResource;

/**
 * Class UserService
 *
 * Handles business logic related to user management.
 */
class CompanyService implements CompanyServiceInterface
{
    protected CompanyRepositoryInterface $companyRepository;

    /**
     * UserService constructor.
     *
     * @param CompanyRepositoryInterface $userRepository
     */
    public function __construct(CompanyRepositoryInterface $companyRepository)
    {
        $this->companyRepository = $companyRepository;
    }

    /**
     * List companies  with pagination, filters, and sorting.
     *
     * @param array $filters
     * @param array $order
     * @param int $limit
     * @param int $page
     * @return array
    */
    public function getList(array $filters = [], array $order = [], int $limit = 10, int $page = 1): array 
    {
        return CompanyResource::collection($this->companyRepository
                    ->listing($filters, $order, $limit, $page))
            ->response()
            ->getData(true);
    }

    /**
     * 
     * @param int $id
     * @return array
     */
    public function detail($id): array 
    {
        return (new CompanyResource(
                    $this->companyRepository->findById($id)))
            ->response()->getData(true);
    }

    /**
     * Register a new company.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
        $company = $this->companyRepository->create($data);

        return (new CompanyResource($company))
                        ->response()->getData(true);
    }

    /**
     * Update an existing user.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        return $this->companyRepository->update($id, $data);
    }

    /**
     * Delete a user by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->companyRepository->delete($id);
    }
}
