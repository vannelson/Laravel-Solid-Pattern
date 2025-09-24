<?php

namespace App\Http\Requests\CarRate;

use Illuminate\Foundation\Http\FormRequest;

class CarRateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'car_id'    => 'required|exists:cars,id',
            'name'      => 'required|string|max:255',
            'rate'      => 'required|numeric|min:0',
            'rate_type' => 'required|string|in:daily,weekly,hourly',
            'start_date'=> 'nullable|date',
            'status'    => 'required|string|in:active,inactive,scheduled',
        ];
    }
}
