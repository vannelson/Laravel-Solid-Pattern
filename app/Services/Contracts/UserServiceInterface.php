<?php

namespace App\Services\Contracts;

interface UserServiceInterface
{
    public function register(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}
