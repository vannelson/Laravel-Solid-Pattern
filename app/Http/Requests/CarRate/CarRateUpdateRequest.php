<?php

namespace App\Http\Requests\CarRate;

use Illuminate\Foundation\Http\FormRequest;

class CarRateUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => 'sometimes|string|max:255',
            'rate'      => 'sometimes|numeric|min:0',
            'rate_type' => 'sometimes|string|in:daily,weekly,hourly',
            'start_date'=> 'nullable|date',
            'status'    => 'sometimes|string|in:active,inactive,scheduled',
        ];
    }
}
