<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Handles authentication logic.
 */
class AuthService
{
    protected UserRepository $userRepository;

    /**
     * AuthService constructor.
     *
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Attempt to log in a user.
     *
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function login(array $data): array
    {
        $user = $this->userRepository->findByEmail($data['email']);

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        return [
            'user' => $user,
            'token' => $user->createToken('authToken')->plainTextToken,
        ];
    }
    
}
