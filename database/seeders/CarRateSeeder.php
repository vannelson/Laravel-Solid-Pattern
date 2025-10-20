<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Car;
use App\Models\CarRate;
use Faker\Factory as Faker;

class CarRateSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('en_PH');

        $dailyRates = [2500, 3200, 3500, 3800, 4200, 4800, 5200];
        $secondaryRates = ['Weekend Flex Rate', 'Corporate Package', 'Peak Season Rate', 'Night Shift Rate'];

        $cars = Car::all();

        foreach ($cars as $car) {
            $baseRateValue = $faker->randomElement($dailyRates);

            CarRate::updateOrCreate(
                [
                    'car_id' => $car->id,
                    'name'   => 'Regular Rate',
                ],
                [
                    'rate'       => $baseRateValue,
                    'rate_type'  => 'daily',
                    'start_date' => now()->subMonths($faker->numberBetween(0, 6))->startOfMonth(),
                    'status'     => 'active',
                ]
            );

            CarRate::updateOrCreate(
                [
                    'car_id' => $car->id,
                    'name'   => $faker->randomElement($secondaryRates),
                ],
                [
                    'rate'       => $baseRateValue + $faker->numberBetween(300, 1200),
                    'rate_type'  => 'daily',
                    'start_date' => now()->addMonths($faker->numberBetween(1, 3))->startOfMonth(),
                    'status'     => 'inactive',
                ]
            );
        }
    }
}
