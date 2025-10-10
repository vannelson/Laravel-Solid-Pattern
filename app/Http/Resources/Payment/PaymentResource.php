<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'booking_id' => $this->booking_id,
            'amount'     => $this->amount,
            'status'     => $this->status,
            'method'     => $this->method,
            'reference'  => $this->reference,
            'meta'       => $this->meta,
            'paid_at'    => optional($this->paid_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
