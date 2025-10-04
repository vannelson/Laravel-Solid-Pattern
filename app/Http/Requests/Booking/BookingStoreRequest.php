<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class BookingStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'car_id'               => 'required|exists:cars,id',
            'borrower_id'          => 'exists:users,id',
            'tenant_id'            => 'required|exists:users,id',
            'start_date'           => 'required|date',
            'end_date'             => 'required|date|after:start_date',
            'expected_return_date' => 'required|date|after_or_equal:end_date',
            'actual_return_date'   => 'nullable|date',
            'destination'          => 'nullable|string|max:255',
            'rate'                 => 'required|numeric|min:0',
            'rate_type'            => 'required|in:daily,hourly',
            'base_amount'          => 'required|numeric|min:0',
            'extra_payment'        => 'nullable|numeric|min:0',
            'discount'             => 'nullable|numeric|min:0',
            'total_amount'         => 'required|numeric|min:0',
            'payment_status'       => 'required|in:Pending,Paid,Cancelled',
            'status'               => 'required|in:Reserved,Ongoing,Completed,Cancelled',
            'identification_type'  => 'required|string|max:50',
            'identification'       => 'required|string|max:255',
            'identification_number' => 'required|string|max:100',
            'identification_images' => 'nullable|array',
            'identification_images.*' => 'nullable|string',
        ];
    }
}
