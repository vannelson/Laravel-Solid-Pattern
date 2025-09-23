<?php

namespace App\Http\Requests\Car;

use Illuminate\Foundation\Http\FormRequest;

class CarUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for updating a car.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'company_id'              => 'sometimes|exists:companies,id',
            'info_make'               => 'sometimes|string|max:255',
            'info_model'              => 'sometimes|string|max:255',
            'info_year'               => 'sometimes|integer|min:1900|max:' . date('Y'),
            'info_age'                => 'nullable|string|max:50',
            'info_carType'            => 'sometimes|string|max:100',
            'info_plateNumber'        => 'sometimes|string|max:50|unique:cars,info_plateNumber,' . $this->route('car'),
            'info_vin'                => 'sometimes|string|max:50|unique:cars,info_vin,' . $this->route('car'),
            'info_availabilityStatus' => 'nullable|string|max:50',
            'info_location'           => 'nullable|string|max:255',
            'info_mileage'            => 'nullable|integer|min:0',

            'spcs_seats'              => 'nullable|integer|min:1',
            'spcs_largeBags'          => 'nullable|integer|min:0',
            'spcs_smallBags'          => 'nullable|integer|min:0',
            'spcs_engineSize'         => 'nullable|integer|min:0',
            'spcs_transmission'       => 'nullable|string|max:50',
            'spcs_fuelType'           => 'nullable|string|max:50',
            'spcs_fuelEfficiency'     => 'nullable|numeric|min:0',

            'features'                => 'nullable|array',
            'profileImage'            => 'nullable|string|max:255',
            'displayImages'           => 'nullable|array',
        ];
    }
}
