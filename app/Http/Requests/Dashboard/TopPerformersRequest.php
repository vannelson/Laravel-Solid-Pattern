<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class TopPerformersRequest extends FormRequest
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
            'preset'         => ['nullable', 'in:rolling_30,rolling_90,month_to_date,year_to_date,custom'],
            'start_date'     => ['required_if:preset,custom', 'date_format:Y-m-d'],
            'end_date'       => ['required_if:preset,custom', 'date_format:Y-m-d'],
            'metric'         => ['nullable', 'in:revenue,occupancy,utilization'],
            'limit'          => ['nullable', 'integer', 'min:1', 'max:20'],
            'vehicle_class'  => ['nullable', 'string'],
            'include_totals' => ['nullable', 'boolean'],
            'timezone'       => ['nullable', 'timezone'],
            'as_of'          => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('include_totals')) {
            $this->merge([
                'include_totals' => filter_var($this->input('include_totals'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }
}
