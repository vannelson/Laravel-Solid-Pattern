<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class RevenueByClassRequest extends FormRequest
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
            'company_id'     => ['nullable', 'integer'],
            'preset'         => ['nullable', 'in:year_to_date,last_90_days,custom'],
            'start_date'     => ['required_if:preset,custom', 'date_format:Y-m-d'],
            'end_date'       => ['required_if:preset,custom', 'date_format:Y-m-d'],
            'timezone'       => ['nullable', 'timezone'],
            'limit'          => ['nullable', 'integer', 'min:1'],
            'include_others' => ['nullable', 'boolean'],
            'as_of'          => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('include_others')) {
            $this->merge([
                'include_others' => filter_var($this->input('include_others'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }
}
