<?php

namespace App\Http\Resources\Booking;

use App\Http\Resources\Car\CarResource;
use App\Http\Resources\Company\CompanyResource;
use App\Http\Resources\Payment\PaymentResource;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                    => $this->id,
            'car'                   => new CarResource($this->whenLoaded('car')),
            'company'               => new CompanyResource($this->whenLoaded('company')),
            'borrower'              => new UserResource($this->whenLoaded('borrower')),
            'tenant'                => new UserResource($this->whenLoaded('tenant')),
            'latest_payment'        => $this->whenLoaded(
                'latestPayment',
                fn () => new PaymentResource($this->latestPayment)
            ),
            'payments'              => $this->whenLoaded(
                'payments',
                fn () => PaymentResource::collection($this->payments)
            ),
            'start_date'            => $this->start_date,
            'end_date'              => $this->end_date,
            'expected_return_date'  => $this->expected_return_date,
            'actual_return_date'    => $this->actual_return_date,
            'destination'           => $this->destination,
            'rate'                  => $this->rate,
            'rate_type'             => $this->rate_type,
            'company_id'            => $this->company_id,
            'base_amount'           => $this->base_amount,
            'extra_payment'         => $this->extra_payment,
            'discount'              => $this->discount,
            'total_amount'          => $this->total_amount,
            'payment_status'        => $this->payment_status,
            'status'                => $this->status,
            'identification_type'   => $this->identification_type,
            'identification'        => $this->identification,
            'identification_number' => $this->identification_number,
            'renter_first_name'      => $this->renter_first_name,
            'renter_middle_name'     => $this->renter_middle_name,
            'renter_last_name'       => $this->renter_last_name,
            'renter_address'         => $this->renter_address,
            'renter_phone_number'    => $this->renter_phone_number,
            'renter_email'           => $this->renter_email,
            'identification_images' => $this->identification_images,
            'is_lock'              => (bool) $this->is_lock,
        ];
    }
}



