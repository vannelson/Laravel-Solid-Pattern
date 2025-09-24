<?php

namespace App\Http\Resources\Booking;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Car\CarResource;
use App\Http\Resources\User\UserResource;

class BookingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                   => $this->id,
            'car'                  => new CarResource($this->whenLoaded('car')),
            'borrower'             => new UserResource($this->whenLoaded('borrower')),
            'tenant'               => new UserResource($this->whenLoaded('tenant')),
            'start_date'           => $this->start_date,
            'end_date'             => $this->end_date,
            'expected_return_date' => $this->expected_return_date,
            'actual_return_date'   => $this->actual_return_date,
            'destination'          => $this->destination,
            'rate'                 => $this->rate,
            'rate_type'            => $this->rate_type,
            'base_amount'          => $this->base_amount,
            'extra_payment'        => $this->extra_payment,
            'discount'             => $this->discount,
            'total_amount'         => $this->total_amount,
            'payment_status'       => $this->payment_status,
            'status'               => $this->status,
        ];
    }
}
