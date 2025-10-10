<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Car;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::first();

        if (!$company) {
            $user = User::firstOrCreate(
                ['email' => 'van.umbay@example.com'],
                [
                    'first_name' => 'Demo',
                    'last_name'  => 'Tenant',
                    'password'   => Hash::make('password'),
                    'type'       => 'tenant',
                    'role'       => 'member',
                ]
            );

            $company = Company::create([
                'user_id'  => $user->id,
                'name'     => 'Demo Rentals',
                'address'  => '123 Demo Street',
                'industry' => 'Car Rental',
                'is_default' => true,
            ]);
        }

        $cars = [
            [
                'make'  => 'Ford',
                'model' => 'Explorer ST 4x4',
                'plate' => 'FORD-EXPL-001',
                'vin'   => 'FORDST4X4SIM0001',
                'type'  => 'SUV',
                'seats' => 7,
                'large_bags' => 4,
                'small_bags' => 3,
                'engine_size' => 3496,
                'fuel_type' => 'Petrol',
            ],
            [
                'make'  => 'Hyundai',
                'model' => 'Accent Hatchback',
                'plate' => 'HYUN-ACCH-001',
                'vin'   => 'HYUNACCHSIM0001',
                'type'  => 'Hatchback',
                'seats' => 5,
                'large_bags' => 2,
                'small_bags' => 2,
                'engine_size' => 1591,
                'fuel_type' => 'Petrol',
            ],
            [
                'make'  => 'Hyundai',
                'model' => 'Elantra',
                'plate' => 'HYUN-ELAN-001',
                'vin'   => 'HYUNELANSIM0001',
                'type'  => 'Sedan',
                'seats' => 5,
                'large_bags' => 2,
                'small_bags' => 2,
                'engine_size' => 1999,
                'fuel_type' => 'Petrol',
            ],
            [
                'make'  => 'Kia',
                'model' => 'Sorento',
                'plate' => 'KIA-SORE-001',
                'vin'   => 'KIASOREAWDSIM01',
                'type'  => 'SUV',
                'seats' => 7,
                'large_bags' => 4,
                'small_bags' => 3,
                'engine_size' => 2497,
                'fuel_type' => 'Diesel',
            ],
            [
                'make'  => 'Subaru',
                'model' => 'Legacy AWD',
                'plate' => 'SUBA-LEGA-001',
                'vin'   => 'SUBALEGAWDSIM01',
                'type'  => 'Sedan',
                'seats' => 5,
                'large_bags' => 3,
                'small_bags' => 2,
                'engine_size' => 2498,
                'fuel_type' => 'Petrol',
            ],
            [
                'make'  => 'Tesla',
                'model' => 'Model 3',
                'plate' => 'TESL-MDL3-001',
                'vin'   => 'TESLMODEL3SIM01',
                'type'  => 'Sedan',
                'seats' => 5,
                'large_bags' => 2,
                'small_bags' => 2,
                'engine_size' => null,
                'fuel_type' => 'Electric',
            ],
            [
                'make'  => 'Toyota',
                'model' => 'Camry or similar',
                'plate' => 'TOYO-CAMR-001',
                'vin'   => 'TOYOCAMRSIM0001',
                'type'  => 'Sedan',
                'seats' => 5,
                'large_bags' => 3,
                'small_bags' => 2,
                'engine_size' => 2487,
                'fuel_type' => 'Hybrid',
            ],
        ];

        foreach ($cars as $carData) {
            Car::updateOrCreate(
                ['info_plateNumber' => $carData['plate']],
                [
                    'company_id'              => $company->id,
                    'info_make'               => $carData['make'],
                    'info_model'              => $carData['model'],
                    'info_year'               => 2024,
                    'info_age'                => '0-3',
                    'info_carType'            => $carData['type'],
                    'info_vin'                => $carData['vin'],
                    'info_availabilityStatus' => 'Available',
                    'info_location'           => 'Main Garage',
                    'info_mileage'            => 0,
                    'spcs_seats'              => $carData['seats'],
                    'spcs_largeBags'          => $carData['large_bags'],
                    'spcs_smallBags'          => $carData['small_bags'],
                    'spcs_engineSize'         => $carData['engine_size'],
                    'spcs_transmission'       => 'Automatic',
                    'spcs_fuelType'           => $carData['fuel_type'],
                    'spcs_fuelEfficiency'     => $carData['fuel_type'] === 'Electric' ? null : 8.5,
                    'features'                => [],
                    'profileImage'            => null,
                    'displayImages'           => [],
                ]
            );
        }
    }
}
