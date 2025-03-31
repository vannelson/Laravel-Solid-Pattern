<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator;

trait ResponseTrait
{
    /**
     * Return a success response.
     *
     * @param string $message
     * @param array|null $data
     * @param int $status
     * @return JsonResponse
     */
    protected function success(string $message, array $data = null, int $status = 200): JsonResponse
    {
        $response = ['message' => $message];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    /**
     * Handle validation errors.
     *
     * @param ValidationException $exception
     * @return JsonResponse
     */
    protected function validationError(ValidationException $exception): JsonResponse
    {
        return response()->json([
            'message' => 'Validation failed.',
            'errors' => $exception->errors(),
        ], 422);
    }

    /**
     * Return an error response.
     *
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    protected function error(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], $status);
    }

    /**
     * Return a success response for login.
     *
     * @param mixed $user
     * @param string $token
     * @return JsonResponse
     */
    protected function successLogin($user, string $token): JsonResponse
    {
        return response()->json([
            'message' => 'Login successful.',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ], 200);
    }

    /**
     * Return a success response with pagination.
     *
     * @param string $message
     * @param array $data
     * @param int $status
     * @return JsonResponse
     */
    protected function successPagination(string $message, $data, int $status = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
             ...$data
          ,
        ], $status);
    }
}
