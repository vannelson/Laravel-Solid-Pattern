<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class HighlightsRequest extends FormRequest
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
            'as_of'        => ['nullable', 'date_format:Y-m-d'],
            'timezone'     => ['nullable', 'timezone'],
            'include_trend'=> ['nullable', 'boolean'],
            'granularity'  => ['nullable', 'in:month'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('include_trend')) {
            $this->merge([
                'include_trend' => filter_var($this->input('include_trend'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }
}
