<?php

namespace App\Http\Requests\Car;

use Illuminate\Foundation\Http\FormRequest;

class CarStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for creating a car.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'company_id'              => 'required|exists:companies,id',
            'info_make'               => 'required|string|max:255',
            'info_model'              => 'required|string|max:255',
            'info_year'               => 'required|integer|min:1900|max:' . date('Y'),
            'info_age'                => 'nullable|string|max:50',
            'info_carType'            => 'required|string|max:100',
            'info_plateNumber'        => 'required|string|max:50|unique:cars,info_plateNumber',
            'info_vin'                => 'required|string|max:50|unique:cars,info_vin',
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
            // Accept URL string or let controller handle file variant
            'profileImage'            => 'nullable',
            'displayImages'           => 'nullable|array',
            'profileImageFile'     => 'nullable|image|mimes:jpeg,jpg,png,gif,webp,avif|max:5120',
            'displayImagesFiles'      => 'nullable|array',
            'displayImagesFiles.*' => 'image|mimes:jpeg,jpg,png,gif,webp,avif|max:5120',
        ];
    }
}
