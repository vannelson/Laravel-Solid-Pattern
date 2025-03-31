<?php

namespace App\Services\Contracts;

/**
 * Interface UserServiceInterface
 *
 * Defines methods for user management.
 */
interface UserServiceInterface
{
    public function register(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}
