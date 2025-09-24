<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class BookingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date'           => 'sometimes|date',
            'end_date'             => 'sometimes|date|after:start_date',
            'expected_return_date' => 'sometimes|date|after_or_equal:end_date',
            'actual_return_date'   => 'nullable|date',
            'destination'          => 'nullable|string|max:255',
            'rate'                 => 'sometimes|numeric|min:0',
            'rate_type'            => 'sometimes|in:daily,hourly',
            'base_amount'          => 'sometimes|numeric|min:0',
            'extra_payment'        => 'nullable|numeric|min:0',
            'discount'             => 'nullable|numeric|min:0',
            'total_amount'         => 'sometimes|numeric|min:0',
            'payment_status'       => 'sometimes|in:Pending,Paid,Cancelled',
            'status'               => 'sometimes|in:Reserved,Ongoing,Completed,Cancelled',
        ];
    }
}
