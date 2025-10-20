<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Car;
use App\Models\Company;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantWalkInBookingSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $tenants = User::where('type', 'tenant')->get();
            if ($tenants->isEmpty()) {
                return;
            }

            $companies = Company::with('cars')->whereIn('user_id', $tenants->pluck('id'))->get();
            if ($companies->isEmpty()) {
                return;
            }

            $startMonth = Carbon::create(2025, 1, 1, 8, 0, 0, 'Asia/Manila');
            $currentMonth = Carbon::create(2025, 11, 1, 8, 0, 0, 'Asia/Manila');
            $endMonth = Carbon::create(2025, 12, 1, 8, 0, 0, 'Asia/Manila');

            $monthCursor = $startMonth->copy();
            $bookingCounter = 1;
            $faker = fake('en_PH');

            while ($monthCursor->lessThanOrEqualTo($endMonth)) {
                $isCurrentMonth = $monthCursor->equalTo($currentMonth);
                $isFutureMonth = $monthCursor->greaterThan($currentMonth);

                foreach ($companies as $company) {
                    if ($company->cars->isEmpty()) {
                        continue;
                    }

                    $carsForMonth = $company->cars->random(min(3, $company->cars->count()));

                    foreach ($carsForMonth as $car) {
                        $firstName = $faker->firstName();
                        $middleName = strtoupper(substr($faker->firstName(), 0, 1)) . '.';
                        $lastName = $faker->lastName();
                        $formattedEmail = sprintf(
                            '%s.%s+%s@walkins.test',
                            Str::lower(Str::slug($firstName, '')),
                            Str::lower(Str::slug($lastName, '')),
                            $bookingCounter
                        );
                        $formattedPhone = '+639' . $faker->numberBetween(100000000, 999999999);
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
                            ->addDays(random_int(0, 3))
                            ->setTime(random_int(7, 10), random_int(0, 59));

                        $durationDays = random_int(3, 6);
                        $end = $start->copy()->addDays($durationDays)->setTime(random_int(17, 20), random_int(0, 59));
                        $expectedReturn = $end->copy();

                        if ($isCurrentMonth || $isFutureMonth) {
                            $actualReturn = null;
                        } else {
                            $actualReturn = $expectedReturn->copy()->addMinutes(random_int(15, 120));
                        }

                        $dailyRate = collect([2500, 3200, 3500, 4200, 4800])->random();
                        $baseAmount = $dailyRate * $durationDays;
                        $extra = ($isCurrentMonth || $isFutureMonth) ? 0 : random_int(0, 3500);
                        $discount = ($isCurrentMonth || $isFutureMonth) ? 0 : (random_int(0, 1) ? random_int(500, 2000) : 0);
                        $total = max($baseAmount + $extra - $discount, 1000);

                        $booking = Booking::updateOrCreate(
                            [
                                'tenant_id'   => $company->user_id,
                                'company_id'  => $company->id,
                                'car_id'      => $car->id,
                                'start_date'  => $start->toDateTimeString(),
                            ],
                            [
                                'company_id'          => $company->id,
                                'borrower_id'          => null,
                                'end_date'             => $end->toDateTimeString(),
                                'expected_return_date' => $expectedReturn->toDateTimeString(),
                                'actual_return_date'   => $actualReturn?->toDateTimeString(),
                                'destination'          => collect([
                                    'Corporate shuttle',
                                    'Airport transfer',
                                    'Provincial run',
                                    'Event shuttle',
                                    'Weekend tour',
                                ])->random(),
                                'rate'                 => $dailyRate,
                                'rate_type'            => 'daily',
                                'base_amount'          => $baseAmount,
                                'extra_payment'        => $extra,
                                'discount'             => $discount,
                                'total_amount'         => $total,
                                'payment_status'       => $isFutureMonth ? 'Pending' : ($isCurrentMonth ? 'Pending' : 'Paid'),
                                'status'               => $isFutureMonth ? 'Reserved' : ($isCurrentMonth ? 'Ongoing' : 'Completed'),
                                'identification_type'  => 'Driver License',
                                'identification'       => 'Philippines Driver License',
                                'identification_number'=> sprintf('DL-%05d-%02d', $bookingCounter, random_int(10, 99)),
                                'renter_first_name'    => $firstName,
                                'renter_middle_name'   => $middleName,
                                'renter_last_name'     => $lastName,
                                'renter_address'       => $address,
                                'renter_phone_number'  => $formattedPhone,
                                'renter_email'         => $formattedEmail,
                                'identification_images'=> [],
                                'is_lock'              => false,
                            ]
                        );

                        if ($isCurrentMonth || $isFutureMonth) {
                            Payment::where('booking_id', $booking->id)->delete();
                        } else {
                            Payment::updateOrCreate(
                                [
                                    'booking_id' => $booking->id,
                                    'reference'  => sprintf('WALKPAY-%s-%s', $car->id, $start->format('Ym')),
                                ],
                                [
                                    'amount'    => $total,
                                    'status'    => 'Paid',
                                    'method'    => collect(['cash', 'bank-transfer', 'credit-card', 'gcash'])->random(),
                                    'paid_at'   => ($actualReturn ?? $expectedReturn)->copy()->addHours(12),
                                    'meta'      => [
                                        'channel' => 'walk-in counter',
                                        'handled_by' => 'Frontdesk Staff',
                                    ],
                                ]
                            );
                        }

                        $bookingCounter++;
                    }
                }

                $monthCursor->addMonth();
            }
        });
    }
}
