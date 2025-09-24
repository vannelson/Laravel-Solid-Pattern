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
        $faker = Faker::create();

        $cars = Car::all();

        foreach ($cars as $car) {
            // 1 Active Regular Rate
            CarRate::create([
                'car_id'    => $car->id,
                'name'      => 'Regular Rate',
                'rate'      => $faker->numberBetween(1500, 4000),
                'rate_type' => 'daily',
                'start_date'=> now(),
                'status'    => 'active',
            ]);

            // 1 Inactive Seasonal Rate
            CarRate::create([
                'car_id'    => $car->id,
                'name'      => $faker->randomElement(['Christmas Price', 'Summer Rate']),
                'rate'      => $faker->numberBetween(2000, 6000),
                'rate_type' => 'daily',
                'start_date'=> $faker->date(),
                'status'    => 'inactive',
            ]);
        }
    }
}
