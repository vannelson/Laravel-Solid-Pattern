<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Company;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantHighVolumeWalkInBookingSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $tenants = User::query()->where('type', 'tenant')->get();
            if ($tenants->isEmpty()) {
                return;
            }

            $companies = Company::query()
                ->with('cars')
                ->whereIn('user_id', $tenants->pluck('id'))
                ->get();

            if ($companies->isEmpty()) {
                return;
            }

            $startMonth = Carbon::create(2022, 1, 1, 8, 0, 0, 'Asia/Manila');
            $endMonth = Carbon::create(2025, 12, 1, 8, 0, 0, 'Asia/Manila');
            $currentMonth = now('Asia/Manila')->startOfMonth();

            $monthCursor = $startMonth->copy();
            $bookingCounter = 1;
            $faker = fake('en_PH');

            while ($monthCursor->lessThanOrEqualTo($endMonth)) {
                $isPastMonth = $monthCursor->lt($currentMonth);
                $isCurrentMonth = $monthCursor->equalTo($currentMonth);

                foreach ($companies as $company) {
                    if ($company->cars->isEmpty()) {
                        continue;
                    }

                    $bookingsThisMonth = random_int(6, 12);

                    for ($i = 0; $i < $bookingsThisMonth; $i++) {
                        $car = $company->cars->random();

                        $firstName = $faker->firstName();
                        $middleInitial = strtoupper(substr($faker->firstName(), 0, 1)) . '.';
                        $lastName = $faker->lastName();

                        $email = sprintf(
                            '%s.%s+hv%d@walkins.test',
                            Str::lower(Str::slug($firstName, '')),
                            Str::lower(Str::slug($lastName, '')),
                            $bookingCounter
                        );

                        $phone = '+639' . $faker->numberBetween(100000000, 999999999);
                        $address = $faker->randomElement([
                            'Makati City',
                            'Pasig City',
                            'Quezon City',
                            'Cebu City',
                            'Davao City',
                            'Taguig City',
                            'Iloilo City',
                            'Baguio City',
                        ]);

                        $start = $monthCursor->copy()
                            ->addDays(random_int(0, 20))
                            ->setTime(random_int(6, 11), random_int(0, 59))
                            ->addMinutes($i); // avoid duplicates within the month

                        $durationDays = random_int(2, 7);
                        $end = $start->copy()->addDays($durationDays)->setTime(random_int(17, 21), random_int(0, 59));
                        $expectedReturn = $end->copy();
                        $actualReturn = $isPastMonth
                            ? $expectedReturn->copy()->addMinutes(random_int(5, 180))
                            : null;

                        $dailyRate = random_int(2200, 5200);
                        $baseAmount = $dailyRate * $durationDays;
                        $extra = $isPastMonth ? random_int(0, 4000) : 0;
                        $discount = $isPastMonth && random_int(0, 1) ? random_int(300, 1800) : 0;
                        $total = max($baseAmount + $extra - $discount, 1200);

                        $status = $isPastMonth ? 'Completed' : ($isCurrentMonth ? 'Ongoing' : 'Reserved');
                        $paymentStatus = $isPastMonth ? 'Paid' : 'Pending';

                        $booking = Booking::updateOrCreate(
                            [
                                'tenant_id'  => $company->user_id,
                                'company_id' => $company->id,
                                'car_id'     => $car->id,
                                'start_date' => $start->toDateTimeString(),
                            ],
                            [
                                'end_date'             => $end->toDateTimeString(),
                                'expected_return_date' => $expectedReturn->toDateTimeString(),
                                'actual_return_date'   => $actualReturn?->toDateTimeString(),
                                'destination'          => $faker->randomElement([
                                    'Corporate shuttle',
                                    'Airport transfer',
                                    'Provincial run',
                                    'Event shuttle',
                                    'Weekend tour',
                                    'Logistics run',
                                ]),
                                'rate'                 => $dailyRate,
                                'rate_type'            => 'daily',
                                'base_amount'          => $baseAmount,
                                'extra_payment'        => $extra,
                                'discount'             => $discount,
                                'total_amount'         => $total,
                                'payment_status'       => $paymentStatus,
                                'status'               => $status,
                                'identification_type'  => 'Driver License',
                                'identification'       => 'Philippines Driver License',
                                'identification_number'=> sprintf('HV-%05d-%02d', $bookingCounter, random_int(10, 99)),
                                'renter_first_name'    => $firstName,
                                'renter_middle_name'   => $middleInitial,
                                'renter_last_name'     => $lastName,
                                'renter_address'       => $address,
                                'renter_phone_number'  => $phone,
                                'renter_email'         => $email,
                                'identification_images'=> [],
                                'is_lock'              => $isPastMonth,
                            ]
                        );

                        if ($isPastMonth) {
                            Payment::updateOrCreate(
                                [
                                    'booking_id' => $booking->id,
                                    'reference'  => sprintf('HV-%s-%s-%d', $car->id, $start->format('Ym'), $i + 1),
                                ],
                                [
                                    'amount'  => $total,
                                    'status'  => 'Paid',
                                    'method'  => $faker->randomElement(['cash', 'bank-transfer', 'credit-card', 'gcash']),
                                    'paid_at' => ($actualReturn ?? $expectedReturn)->copy()->addHours(random_int(1, 8)),
                                    'meta'    => [
                                        'channel'    => 'walk-in counter',
                                        'handled_by' => 'High volume import',
                                    ],
                                ]
                            );
                        } else {
                            Payment::where('booking_id', $booking->id)->delete();
                        }

                        $bookingCounter++;
                    }
                }

                $monthCursor->addMonth();
            }
        });
    }
}
