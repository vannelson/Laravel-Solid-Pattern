<?php

namespace App\Http\Resources\Car;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Company\CompanyResource;

class CarResource extends JsonResource
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
            'id'                     => $this->id,
            'company_id'             => $this->company_id,
            'company'                => new CompanyResource($this->whenLoaded('company')),

            // Basic Info
            'info_make'              => $this->info_make,
            'info_model'             => $this->info_model,
            'info_year'              => $this->info_year,
            'info_age'               => $this->info_age,
            'info_carType'           => $this->info_carType,
            'info_plateNumber'       => $this->info_plateNumber,
            'info_vin'               => $this->info_vin,
            'info_availabilityStatus'=> $this->info_availabilityStatus,
            'info_location'          => $this->info_location,
            'info_mileage'           => $this->info_mileage,

            // Specifications
            'spcs_seats'             => $this->spcs_seats,
            'spcs_largeBags'         => $this->spcs_largeBags,
            'spcs_smallBags'         => $this->spcs_smallBags,
            'spcs_engineSize'        => $this->spcs_engineSize,
            'spcs_transmission'      => $this->spcs_transmission,
            'spcs_fuelType'          => $this->spcs_fuelType,
            'spcs_fuelEfficiency'    => $this->spcs_fuelEfficiency,

            // Features & Images
            'features'               => $this->features,
            'profileImage'           => $this->profileImage,
            'displayImages'          => $this->displayImages,
        ];
    }
}
