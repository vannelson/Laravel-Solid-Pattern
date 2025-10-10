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
            'payment_status'       => 'sometimes|in:Pending,Paid,Cancelled,Failed,Refunded',
            'status'               => 'sometimes|in:Reserved,Ongoing,Completed,Cancelled',
            'identification_type'  => 'sometimes|string|max:50',
            'identification'       => 'sometimes|string|max:255',
            'identification_number' => 'sometimes|string|max:100',
            'renter_first_name'      => 'sometimes|required|string|max:255',
            'renter_middle_name'     => 'sometimes|required|string|max:255',
            'renter_last_name'       => 'sometimes|required|string|max:255',
            'renter_address'         => 'sometimes|required|string|max:255',
            'renter_phone_number'    => 'sometimes|required|string|max:50',
            'renter_email'           => 'sometimes|required|string|email|max:255',
            'identificationImagesFiles' => 'sometimes|array',
            'identificationImagesFiles.*' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,avif|max:5120',
            'identification_images' => 'sometimes|array',
            'identification_images.*' => 'nullable|string',
            'is_lock'             => 'sometimes|boolean',
        ];
    }
}
