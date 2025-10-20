<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DashboardSummaryRequest extends FormRequest
{
    /**
     * Authorise all authenticated requests; guard handles auth.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for the dashboard summary.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'year'        => ['sometimes', 'integer', 'min:2000', 'max:2100'],
            'company_id'  => ['sometimes', 'integer', 'exists:companies,id'],
            'car_id'      => ['sometimes', 'integer', 'exists:cars,id'],
            'status'      => ['sometimes', 'array'],
            'status.*'    => ['string', 'max:50'],
            'date_field'  => [
                'sometimes',
                'string',
                Rule::in([
                    'actual_return',
                    'actual_return_date',
                    'end_date',
                    'expected_return',
                    'expected_return_date',
                    'start_date',
                ]),
            ],
            'use_payments'=> ['sometimes', 'boolean'],
            'preset'      => [
                'sometimes',
                'string',
                Rule::in([
                    'year_to_date',
                    'last_30_days',
                    'quarter_to_date',
                    'custom',
                ]),
            ],
            'start_date'  => ['sometimes', 'date_format:Y-m-d'],
            'end_date'    => ['sometimes', 'date_format:Y-m-d'],
            'include_trend' => ['sometimes', 'boolean'],
            'currency'    => ['sometimes', 'string', 'max:10'],
        ];
    }

    /**
     * Prepare input for validation.
     */
    protected function prepareForValidation(): void
    {
        $booleanFields = ['use_payments', 'include_trend'];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var(
                        $this->input($field),
                        FILTER_VALIDATE_BOOLEAN,
                        FILTER_NULL_ON_FAILURE
                    ),
                ]);
            }
        }
    }

    /**
     * Custom validation logic for presets and dates.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $preset = $this->input('preset', 'year_to_date');
            $start = $this->input('start_date');
            $end = $this->input('end_date');

            if ($preset === 'custom') {
                if (!$start || !$end) {
                    $validator->errors()->add('start_date', 'start_date and end_date are required when preset is custom.');
                    return;
                }

                if ($start > $end) {
                    $validator->errors()->add('start_date', 'start_date must be before or equal to end_date.');
                }
            }

            if ($preset !== 'custom' && ($start || $end)) {
                $validator->errors()->add('preset', 'start_date and end_date are only allowed when preset is custom.');
            }
        });
    }
}
