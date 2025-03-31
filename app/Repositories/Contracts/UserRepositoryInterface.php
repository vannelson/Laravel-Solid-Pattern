<?php

namespace App\Repositories\Contracts;

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
}
