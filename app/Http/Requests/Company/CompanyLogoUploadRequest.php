<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class CompanyLogoUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for uploading a company logo.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'image' => 'required|mimes:jpeg,jpg,png,gif,webp,avif|max:5120',
        ];
    }
}
