<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MonthlySalesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id'      => ['nullable', 'integer'],
            'timezone'        => ['nullable', 'timezone'],
            'include_previous'=> ['nullable', 'boolean'],
            'granularity'     => ['nullable', 'in:month'],
            'year'            => ['nullable', 'integer', 'digits:4'],
            'start_year'      => ['nullable', 'integer', 'digits:4'],
            'end_year'        => ['nullable', 'integer', 'digits:4'],
            'as_of'           => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('include_previous')) {
            $value = $this->input('include_previous');
            $this->merge([
                'include_previous' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $year = $this->input('year');
            $startYear = $this->input('start_year');
            $endYear = $this->input('end_year');

            if ($year !== null && ($startYear !== null || $endYear !== null)) {
                $validator->errors()->add('year', 'Use either year or start_year/end_year, not both.');
            }

            if ($startYear !== null && $endYear !== null && (int) $endYear < (int) $startYear) {
                $validator->errors()->add('end_year', 'The end year must be greater than or equal to the start year.');
            }
        });
    }
}
