<?php

namespace App\Services;

use App\Http\Resources\Company\CompanyResource;
use App\Repositories\UserRepository;
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

        $activeCompany = $user->activeCompany()->with('user')->first();

        if (!$activeCompany) {
            $activeCompany = $user->companies()->with('user')->oldest('id')->first();
        }

        $userData = $user->toArray();
        $userData['active_company'] = $activeCompany
            ? CompanyResource::make($activeCompany)->toArray(request())
            : null;

        return [
            'user' => $userData,
            'token' => $user->createToken('authToken')->plainTextToken,
        ];
    }
    
}
