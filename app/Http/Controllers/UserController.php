<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UserRegisterRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Services\Contracts\UserServiceInterface;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    use ResponseTrait;

    protected UserServiceInterface $userService;

    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = Arr::get($request->all(), 'filters', []);
        $order   = Arr::get($request->all(), 'order', ['id', 'desc']);
        $limit   = (int) Arr::get($request->all(), 'limit', 10);
        $page    = (int) Arr::get($request->all(), 'page', 1);
    
        try {
            $data = $this->userService->getList($filters, $order, $limit, $page);
            return $this->successPagination('Users retrieved successfully!', $data);
        } catch (\Exception $e) {
            return $this->error('Failed to load song.', 500);
        }
    }

    /**
     * Store a newly created user (register).
     */
    public function store(UserRegisterRequest $request): JsonResponse
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
     * Display a specific user.
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->userService->detail($id);
        return $this->success('User registered successfully!', $user);
    }

    /**
     * Update an existing user.
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
     * Remove a user.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->userService->delete($id);
            return $this->success('User deleted successfully!');
        } catch (\Exception $e) {
            return $this->error('Failed to delete user.', 500);
        }
    }
}
