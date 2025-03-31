<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRegisterRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Services\Contracts\UserServiceInterface;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class UserController
 */
class UserController extends Controller
{
    use ResponseTrait;

    protected UserServiceInterface $userService;

    /**
     * UserController constructor.
     *
     * @param UserServiceInterface $userService
     */
    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Register a new user.
     *
     * @param UserRegisterRequest 
     * @return JsonResponse
     */
    public function register(UserRegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->register($request->validated());
            return $this->success('User registered successfully!', $user);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            return $this->error('Failed to register user.', 500);
        }
    }

    /**
     * Update an existing user.
     *
     * @param UserUpdateRequest 
     * @param int $id
     * @return JsonResponse
     */
    public function update(UserUpdateRequest $request, int $id): JsonResponse
    {
        try {
            $this->userService->update($id, $request->validated());
            return $this->success('User updated successfully!');
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            return $this->error('Failed to update user.', 500);
        }
    }

    /**
     * Delete a user.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function delete(int $id): JsonResponse
    {
        try {
            $this->userService->delete($id);
            return $this->success('User deleted successfully!');
        } catch (\Exception $e) {
            return $this->error('Failed to delete user.', 500);
        }
    }
}
