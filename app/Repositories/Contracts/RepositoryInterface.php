<?php

namespace App\Repositories\Contracts;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface RepositoryInterface
 *
 * Defines common repository methods for database interactions.
 */
interface RepositoryInterface
{
    /**
     * Find a record by ID.
     *
     * @param int $id
     * @return Model
     */
    public function findById(int $id): Model;

    /**
     * Create a new record.
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Update an existing record.
     *
     * @param int $id
     * @param array $data
     * @return int
     */
    public function update(int $id, array $data): int;

    /**
     * Update an existing record and return the updated model.
     *
     * @param int $id
     * @param array $data
     * @return Model
     */
    public function updateAndGet(int $id, array $data): Model;

    /**
     * Delete a record.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
}
