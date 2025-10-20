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
        $cars         = Car::pluck('id')->toArray();
        $borrowers    = User::where('type', 'borrower')->pluck('id')->toArray();
        $tenantId     = 8; // fallback
        $destinations = [
            'Airport', 'City Center', 'Beach Resort', 'Hotel District',
            'Conference Venue', 'Shopping Mall', 'Industrial Park',
        ];
        $paymentStatuses = ['Pending', 'Paid', 'Cancelled', 'Failed', 'Refunded'];
        $methods         = ['cash', 'bank-transfer', 'credit-card', 'gcash'];

        for ($i = 0; $i < 30; $i++) {
            $carId      = fake()->randomElement($cars);
            $borrowerId = fake()->randomElement($borrowers);
            $car        = Car::find($carId);

            if ($car === null) {
                continue;
            }

            $startDate = fake()->dateTimeBetween('-10 days', '+5 days');
            $endDate   = (clone $startDate)->modify('+' . rand(1, 5) . ' days');
            $expected  = (clone $endDate);
            $actual    = rand(0, 1) ? (clone $expected)->modify('+' . rand(0, 2) . ' days') : null;

            $rate     = fake()->numberBetween(1500, 4000);
            $rateType = fake()->randomElement(['daily', 'hourly']);
            $base     = $rate * rand(1, 5);
            $extra    = $actual && $actual > $expected ? fake()->numberBetween(500, 1500) : 0;
            $discount = rand(0, 1) ? fake()->numberBetween(100, 500) : 0;
            $total    = $base + $extra - $discount;

            $paymentStatus = fake()->randomElement($paymentStatuses);

            $booking = Booking::create([
                'car_id'               => $carId,
                'company_id'           => $car->company_id,
                'borrower_id'          => $borrowerId,
                'tenant_id'            => $tenantId,
                'start_date'           => $startDate,
                'end_date'             => $endDate,
                'expected_return_date' => $expected,
                'actual_return_date'   => $actual,
                'destination'          => fake()->randomElement($destinations),
                'rate'                 => $rate,
                'rate_type'            => $rateType,
                'base_amount'          => $base,
                'extra_payment'        => $extra,
                'discount'             => $discount,
                'total_amount'         => $total,
                'payment_status'       => $paymentStatus,
                'status'               => fake()->randomElement(['Reserved', 'Ongoing', 'Completed', 'Cancelled']),
                'is_lock'              => false,
            ]);

            if (in_array($paymentStatus, ['Paid', 'Refunded'], true)) {
                $booking->payments()->create([
                    'amount'    => $total,
                    'status'    => $paymentStatus,
                    'method'    => fake()->randomElement($methods),
                    'reference' => strtoupper(fake()->bothify('PAY###??')),
                    'paid_at'   => fake()->dateTimeBetween('-5 days', 'now'),
                    'meta'      => [
                        'channel' => 'seeded',
                    ],
                ]);
            }
        }
    }
}
