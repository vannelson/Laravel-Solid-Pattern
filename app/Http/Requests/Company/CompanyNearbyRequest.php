<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class CompanyNearbyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lat'              => 'required|numeric|between:-90,90',
            'lng'              => 'required|numeric|between:-180,180',
            'radius'           => 'nullable|integer|min:1|max:50000',
            'limit'            => 'nullable|integer|min:1|max:100',
            'with_cars'        => 'nullable|boolean',
            'include_distance' => 'nullable|boolean',
            'min_distance'     => 'nullable|integer|min:0',
            'filters'          => 'nullable|array',
            'filters.industry' => 'nullable|string|max:255',
            'filters.name'     => 'nullable|string|max:255',
            'filters.car_type' => 'nullable|string|max:255',
        ];
    }

    protected function prepareForValidation(): void
    {
        $booleanKeys = ['with_cars', 'include_distance'];

        foreach ($booleanKeys as $key) {
            if ($this->has($key)) {
                $value = $this->input($key);
                $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($normalized !== null) {
                    $this->merge([$key => $normalized]);
                }
            }
        }
    }
}

