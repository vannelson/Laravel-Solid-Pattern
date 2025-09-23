<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class CompanyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // adjust if needed
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'address'  => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
        ];
    }
}
