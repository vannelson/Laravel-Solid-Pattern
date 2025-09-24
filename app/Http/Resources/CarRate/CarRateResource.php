<?php

namespace App\Http\Resources\CarRate;

use Illuminate\Http\Resources\Json\JsonResource;

class CarRateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id'        => $this->id,
            'car_id'    => $this->car_id,
            'name'      => $this->name,
            'rate'      => $this->rate,
            'rate_type' => $this->rate_type,
            'start_date'=> $this->start_date,
            'status'    => $this->status,
        ];
    }
}
