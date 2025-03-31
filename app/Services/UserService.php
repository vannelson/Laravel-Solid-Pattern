<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Services\Contracts\UserServiceInterface;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource;

/**
 * Class UserService
 *
 * Handles business logic related to user management.
 */
class UserService implements UserServiceInterface
{
    protected UserRepository $userRepository;

    /**
     * UserService constructor.
     *
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Register a new user.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
        $data['password'] = Hash::make($data['password']);
        $user = $this->userRepository->create($data);

        return (new UserResource($user))
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
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        return $this->userRepository->update($id, $data);
    }

    /**
     * Delete a user by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->userRepository->delete($id);
    }
}
