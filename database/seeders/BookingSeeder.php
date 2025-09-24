<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\Car;
use App\Models\User;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $cars      = Car::pluck('id')->toArray();
        $borrowers = User::where('type', 'borrower')->pluck('id')->toArray();
        $tenantId  =  8; // fallback

        $destinations = [
            'Airport', 'City Center', 'Beach Resort', 'Hotel District',
            'Conference Venue', 'Shopping Mall', 'Industrial Park'
        ];

        $rows = [];

        for ($i = 0; $i < 30; $i++) {
            $carId      = fake()->randomElement($cars);
            $borrowerId = fake()->randomElement($borrowers);

            $startDate  = fake()->dateTimeBetween('-10 days', '+5 days');
            $endDate    = (clone $startDate)->modify('+'.rand(1, 5).' days');
            $expected   = (clone $endDate);
            $actual     = rand(0,1) ? (clone $expected)->modify('+'.rand(0, 2).' days') : null;

            $rate       = fake()->numberBetween(1500, 4000);
            $rateType   = fake()->randomElement(['daily', 'hourly']);
            $base       = $rate * rand(1, 5);
            $extra      = $actual && $actual > $expected ? fake()->numberBetween(500, 1500) : 0;
            $discount   = rand(0,1) ? fake()->numberBetween(100, 500) : 0;
            $total      = $base + $extra - $discount;

            $rows[] = [
                'car_id'              => $carId,
                'borrower_id'         => $borrowerId,
                'tenant_id'           => $tenantId,
                'start_date'          => $startDate,
                'end_date'            => $endDate,
                'expected_return_date'=> $expected,
                'actual_return_date'  => $actual,
                'destination'         => fake()->randomElement($destinations),
                'rate'                => $rate,
                'rate_type'           => $rateType,
                'base_amount'         => $base,
                'extra_payment'       => $extra,
                'discount'            => $discount,
                'total_amount'        => $total,
                'payment_status'      => fake()->randomElement(['Pending', 'Paid', 'Cancelled']),
                'status'              => fake()->randomElement(['Reserved', 'Ongoing', 'Completed', 'Cancelled']),
                'created_at'          => now(),
                'updated_at'          => now(),
            ];
        }

        Booking::insert($rows);
    }
}
