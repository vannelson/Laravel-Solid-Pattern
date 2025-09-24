<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BaseRepository
 *
 * Implements common repository methods.
 */
abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Find a record by ID.
     *
     * @param int $id
     * @return Model
     */
    public function findById(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Create a new record.
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing record.
     *
     * @param int $id
     * @param array $data
     * @return int
     */
    public function update(int $id, array $data): int
    {
        return $this->model->where('id', $id)->update($data);
    }

    /**
     * Update an existing record and return the updated model.
     *
     * @param int $id
     * @param array $data
     * @return Model
     */
    public function updateAndGet(int $id, array $data): Model
    {
        // Find the model instance by id
        $model = $this->model->findOrFail($id);
        // Update the model's data
        $model->update($data);
        
        return $model;
    }

    /**
     * Delete a record.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $model = $this->model->find($id);
        if ($model) {
            return $model->delete(); 
        }
        return false; 
    }

    /**
     * Find a user by email.
     *
     * @param string $email
     * @return \App\Models\User|null
     */
    public function findByEmail(string $email)
    {
        return $this->model->where('email', $email)->first();
    }
}
