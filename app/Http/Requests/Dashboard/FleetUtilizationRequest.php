<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class FleetUtilizationRequest extends FormRequest
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
            'company_id'   => ['nullable', 'integer'],
            'preset'       => ['nullable', 'string', 'in:year_to_date,last_30_days,quarter_to_date,custom'],
            'start_date'   => ['required_if:preset,custom', 'date_format:Y-m-d'],
            'end_date'     => ['required_if:preset,custom', 'date_format:Y-m-d'],
            'timezone'     => ['nullable', 'timezone'],
            'include_trend'=> ['nullable', 'boolean'],
            'granularity'  => ['nullable', 'string', 'in:day,hour'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('include_trend')) {
            $value = $this->input('include_trend');
            $converted = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $this->merge(['include_trend' => $converted]);
        }
    }
}
