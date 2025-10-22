<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class UpcomingBookingsRequest extends FormRequest
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
            'start_date'      => ['nullable', 'date_format:Y-m-d'],
            'end_date'        => ['nullable', 'date_format:Y-m-d'],
            'limit'           => ['nullable', 'integer', 'min:1', 'max:50'],
            'timezone'        => ['nullable', 'timezone'],
            'include_waitlist'=> ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('include_waitlist')) {
            $this->merge([
                'include_waitlist' => filter_var($this->input('include_waitlist'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }
}
