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
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude'=> 'nullable|numeric|between:-180,180',
            'industry' => 'nullable|string|max:255',
            'logo'     => 'nullable|image|max:2048',
            'is_default' => 'nullable|boolean',
            'user_id' => 'required|int'
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_default')) {
            $value = $this->input('is_default');

            if ($value === '' || $value === null) {
                $this->merge(['is_default' => null]);
                return;
            }

            $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($normalized !== null) {
                $this->merge(['is_default' => $normalized]);
            }
        }

        foreach (['latitude', 'longitude'] as $key) {
            if ($this->has($key)) {
                $value = $this->input($key);
                if ($value === '' || $value === null) {
                    $this->merge([$key => null]);
                    continue;
                }

                $normalized = filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
                if ($normalized !== null) {
                    $this->merge([$key => $normalized]);
                }
            }
        }
    }
}
