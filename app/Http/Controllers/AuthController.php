<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Class AuthController
 *
 * Handles user authentication.
 */
class AuthController extends Controller
{
    use ResponseTrait;

    protected AuthService $authService;

    /**
     * AuthController constructor.
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle user login.
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Validate input
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            // Attempt login
            $authData = $this->authService->login($validated);

            $userArray = is_array($authData['user']) ? 
                    $authData['user'] : $authData['user']->toArray();

            return $this->successLogin($userArray, $authData['token']);
        } catch (ValidationException $e) {
            return $this->error('Invalid credentials.', 401);
        } catch (\Exception $e) {
            return $this->error('Login failed.', 500);
        }
    }
}
