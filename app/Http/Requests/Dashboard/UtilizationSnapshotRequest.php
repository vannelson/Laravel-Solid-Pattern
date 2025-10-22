<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class UtilizationSnapshotRequest extends FormRequest
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
            'company_id'       => ['nullable', 'integer'],
            'as_of'            => ['nullable', 'date'],
            'timezone'         => ['nullable', 'timezone'],
            'include_breakdown'=> ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('include_breakdown')) {
            $this->merge([
                'include_breakdown' => filter_var($this->input('include_breakdown'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }
}
