<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SongUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'artist' => 'sometimes|string|max:255',
            'duration' => 'sometimes|integer|min:1',
        ];
    }
}
