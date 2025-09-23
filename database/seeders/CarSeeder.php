<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Car;
use App\Models\Company;
use Faker\Factory as Faker;

class CarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Make sure we have companies
        if (Company::count() === 0) {
            $this->call(CompanySeeder::class);
        }

        // Create 20 cars
        for ($i = 0; $i < 20; $i++) {
            Car::create([
                'company_id'              => Company::inRandomOrder()->first()->id,
                'info_make'               => $faker->randomElement([
                    'Toyota', 'Honda', 'Ford', 'Chevrolet', 'Nissan', 'Hyundai',
                    'Kia', 'Volkswagen', 'BMW', 'Mercedes-Benz', 'Audi', 'Mazda'
                ]),
                'info_model'              => $faker->word,
                'info_year'               => $faker->numberBetween(2000, date('Y')),
                'info_age'                => $faker->randomElement(['0-3', '4-7', '8+']),
                'info_carType'            => $faker->randomElement(['SUV', 'Sedan', 'Hatchback', 'Truck', 'Van']),
                'info_plateNumber'        => strtoupper($faker->bothify('???-####')),
                'info_vin'                => strtoupper($faker->bothify('1HGCM82633A######')),
                'info_availabilityStatus' => $faker->randomElement(['Available', 'Rented', 'Maintenance']),
                'info_location'           => $faker->city,
                'info_mileage'            => $faker->numberBetween(0, 200000),

                'spcs_seats'              => $faker->numberBetween(2, 7),
                'spcs_largeBags'          => $faker->numberBetween(0, 3),
                'spcs_smallBags'          => $faker->numberBetween(0, 4),
                'spcs_engineSize'         => $faker->numberBetween(1000, 4000),
                'spcs_transmission'       => $faker->randomElement(['Automatic', 'Manual']),
                'spcs_fuelType'           => $faker->randomElement(['Petrol', 'Diesel', 'Hybrid', 'Electric']),
                'spcs_fuelEfficiency'     => $faker->randomFloat(1, 5, 15),

                'features'                => $faker->randomElements(
                    ['Air Conditioning', 'GPS', 'Bluetooth', 'Backup Camera', 'Cruise Control', 'Heated Seats'],
                    rand(2, 5)
                ),
                'profileImage'            => $faker->imageUrl(640, 480, 'cars', true),
                'displayImages'           => [
                    $faker->imageUrl(640, 480, 'cars', true),
                    $faker->imageUrl(640, 480, 'cars', true)
                ],
            ]);
        }
    }
}
