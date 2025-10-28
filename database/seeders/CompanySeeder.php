<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\User;
use Faker\Factory as Faker;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Make sure we have at least some users
        if (User::count() === 0) {
            User::factory(5)->create(); // create 5 fake users if none exist
        }

        // Create 10 companies assigned to random users
        for ($i = 0; $i < 10; $i++) {
            Company::create([
                'user_id'  => User::inRandomOrder()->first()->id,
                'name'     => $faker->company,
                'address'  => $faker->address,
                'latitude' => $faker->latitude(),
                'longitude'=> $faker->longitude(),
                'industry' => $faker->word,
            ]);
        }
    }
}
