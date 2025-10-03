<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserUpdateRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'first_name' => 'sometimes|required|string|max:255',
            'middle_name' => 'sometimes|nullable|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $this->route('id'),
            'password' => 'sometimes|required|string|min:6|confirmed',
            'type' => 'sometimes|required|string|in:user,admin',
            'role' => 'sometimes|required|string|in:member,editor,admin',
        ];
    }

    /**
     * Handle failed validation and return a JSON response.
     *
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validation failed!',
            'errors' => $validator->errors()
        ], 422));
    }
}
