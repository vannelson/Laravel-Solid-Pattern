<?php

namespace App\Http\Requests\Car;

use Illuminate\Foundation\Http\FormRequest;

class CarImageUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for uploading a car image.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            // Accept common web image types (including AVIF), up to ~5MB
            'image' => 'required|mimes:jpeg,jpg,png,gif,webp,avif|max:5120',
        ];
    }
}
