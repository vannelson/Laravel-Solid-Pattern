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
        $secondaryRates = [
            'Weekend Flex Rate',
            'Corporate Package',
            'Peak Season Rate',
            'Night Shift Rate',
            'Holiday Special',
        ];

        $cars = Car::all();

        foreach ($cars as $car) {
            $baseRateValue = $faker->numberBetween(2000, 5000);

            CarRate::updateOrCreate(
                [
                    'car_id' => $car->id,
                    'name'   => 'Regular Rate',
                ],
                [
                    'rate'       => $baseRateValue,
                    'rate_type'  => 'daily',
                    'start_date' => now()->subMonths($faker->numberBetween(0, 2))->startOfMonth(),
                    'status'     => 'active',
                ]
            );

            $otherRates = $faker->randomElements($secondaryRates, $faker->numberBetween(1, 2));

            foreach ($otherRates as $rateName) {
                CarRate::updateOrCreate(
                    [
                        'car_id' => $car->id,
                        'name'   => $rateName,
                    ],
                    [
                        'rate'       => $baseRateValue + $faker->numberBetween(200, 1200),
                        'rate_type'  => $faker->randomElement(['daily', 'weekly']),
                        'start_date' => now()->addMonths($faker->numberBetween(1, 4))->startOfMonth(),
                        'status'     => 'inactive',
                    ]
                );
            }
        }
    }
}
