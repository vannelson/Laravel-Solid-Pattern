<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantCarInventorySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $faker = fake('en_PH');

            $templates = [
                ['Toyota', 'Hiace', 'Van', 2.8, 'Diesel'],
                ['Toyota', 'Innova', 'MPV', 2.0, 'Diesel'],
                ['Hyundai', 'Staria', 'Van', 2.2, 'Diesel'],
                ['Nissan', 'Urvan', 'Van', 2.5, 'Diesel'],
                ['Ford', 'Transit', 'Van', 2.2, 'Diesel'],
                ['Kia', 'Carnival', 'MPV', 3.5, 'Gasoline'],
                ['Toyota', 'Coaster', 'Mini Bus', 4.0, 'Diesel'],
                ['Mercedes-Benz', 'Vito', 'Van', 2.0, 'Diesel'],
                ['Chevrolet', 'Trailblazer', 'SUV', 2.8, 'Diesel'],
                ['Isuzu', 'Traviz', 'Light Truck', 2.5, 'Diesel'],
            ];

            $companies = Company::all();

            foreach ($companies as $companyIndex => $company) {
                $existingCount = Car::where('company_id', $company->id)->count();
                $targetCount = max(15, $existingCount);
                $toGenerate = $targetCount - $existingCount;

                if ($toGenerate <= 0) {
                    continue;
                }

                for ($i = 0; $i < $toGenerate; $i++) {
                    $template = $templates[($companyIndex + $i) % count($templates)];
                    [$make, $model, $type, $engineSize, $fuelType] = $template;

                    $year = $faker->numberBetween(date('Y') - 4, date('Y'));
                    $age = max(date('Y') - $year, 1);
                    $plateSuffix = str_pad((string) ($companyIndex * 100 + $i + 10), 4, '0', STR_PAD_LEFT);
                    $platePrefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $company->name), 0, 3));
                    if (strlen($platePrefix) < 3) {
                        $platePrefix = str_pad($platePrefix, 3, 'X');
                    }

                    Car::updateOrCreate(
                        [
                            'company_id'       => $company->id,
                            'info_plateNumber' => "{$platePrefix}-{$plateSuffix}",
                        ],
                        [
                            'info_make'               => $make,
                            'info_model'              => $model,
                            'info_year'               => $year,
                            'info_age'                => "{$age} years",
                            'info_carType'            => $type,
                            'info_vin'                => strtoupper(Str::random(17)),
                            'info_availabilityStatus' => $faker->randomElement(['Available', 'Booked', 'Maintenance']),
                            'info_location'           => $faker->randomElement([
                                $company->address,
                                'Quezon City Depot',
                                'Makati Garage',
                                'NAIA Terminal Parking',
                                'Ortigas Hub',
                                'Cebu Workshop',
                                'Davao Transport Yard',
                            ]),
                            'info_mileage'            => $faker->numberBetween(5000, 120000),
                            'spcs_seats'              => $faker->numberBetween(7, 22),
                            'spcs_largeBags'          => $faker->numberBetween(2, 6),
                            'spcs_smallBags'          => $faker->numberBetween(3, 8),
                            'spcs_engineSize'         => (int) ($engineSize * 1000),
                            'spcs_transmission'       => $faker->randomElement(['Automatic', 'Manual']),
                            'spcs_fuelType'           => $fuelType,
                            'spcs_fuelEfficiency'     => $faker->randomFloat(1, 7.5, 13.5),
                            'features'                => [
                                'On-board WiFi',
                                'USB charging ports',
                                $faker->randomElement(['Dashcam', 'GPS Navigation', 'Leather seats', 'Dual aircon']),
                            ],
                            'profileImage'            => null,
                            'displayImages'           => [],
                        ]
                    );
                }
            }
        });
    }
}
