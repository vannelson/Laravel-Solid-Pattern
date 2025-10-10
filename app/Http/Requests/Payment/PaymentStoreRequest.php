<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class PaymentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount'    => 'required|numeric|min:0',
            'status'    => 'nullable|in:Pending,Paid,Failed,Refunded,Cancelled',
            'method'    => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:100',
            'meta'      => 'nullable|array',
            'paid_at'   => 'nullable|date',
        ];
    }
}
