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

class TenantBookingPaymentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $borrowerIds = $this->ensureBorrowers();
            if ($borrowerIds->isEmpty()) {
                return;
            }

            $companies = Company::with('cars:id,company_id')->get();
            if ($companies->isEmpty()) {
                return;
            }

            $rateOptions = [2500, 3200, 3500, 4200, 4800];
            $destinations = [
                'Makati CBD loop',
                'BGC corporate run',
                'NAIA airport transfer',
                'Cebu IT Park shuttle',
                'Davao industrial park',
                'Ortigas corporate hub',
                'Clark Freeport logistics',
                'Nuvali tech park',
            ];

            $startMonth = Carbon::createFromDate(2025, 1, 1, 'Asia/Manila');
            $endMonth = Carbon::createFromDate(2025, 11, 1, 'Asia/Manila');

            $monthCursor = $startMonth->copy();

            while ($monthCursor->lessThanOrEqualTo($endMonth)) {
                $isCurrentMonth = $monthCursor->equalTo($endMonth);

                foreach ($companies as $company) {
                    $tenantId = $company->user_id;
                    $cars = $company->cars;

                    if ($cars->isEmpty()) {
                        continue;
                    }

                    $carsForMonth = $cars->random(min(3, $cars->count()));

                    foreach ($carsForMonth as $car) {
                        $borrowerId = $borrowerIds->random();
                        $days = random_int(3, 6);

                        $start = $monthCursor->copy()
                            ->addDays(random_int(0, 6))
                            ->setTime(random_int(7, 10), random_int(0, 59));

                        $end = $start->copy()->addDays($days)->setTime(random_int(17, 20), random_int(0, 59));

                        $dailyRate = $rateOptions[array_rand($rateOptions)];
                        $baseAmount = $dailyRate * $days;
                        $extraPayment = $isCurrentMonth ? 0 : random_int(0, 4000);
                        $discount = $isCurrentMonth ? 0 : (random_int(0, 1) ? random_int(500, 2500) : 0);
                        $totalAmount = max($baseAmount + $extraPayment - $discount, 1000);

                        $status = $isCurrentMonth ? 'Ongoing' : 'Completed';
                        $paymentStatus = $isCurrentMonth ? 'Pending' : 'Paid';
                        $actualReturn = $isCurrentMonth
                            ? null
                            : $end->copy()->addMinutes(random_int(30, 120));

                        $booking = Booking::updateOrCreate(
                            [
                                'tenant_id'   => $tenantId,
                                'company_id'  => $company->id,
                                'car_id'      => $car->id,
                                'borrower_id' => $borrowerId,
                                'start_date'  => $start->toDateTimeString(),
                            ],
                            [
                                'company_id'          => $company->id,
                                'end_date'             => $end->toDateTimeString(),
                                'expected_return_date' => $end->toDateTimeString(),
                                'actual_return_date'   => $actualReturn?->toDateTimeString(),
                                'destination'          => $destinations[array_rand($destinations)],
                                'rate'                 => $dailyRate,
                                'rate_type'            => 'daily',
                                'base_amount'          => $baseAmount,
                                'extra_payment'        => $extraPayment,
                                'discount'             => $discount,
                                'total_amount'         => $totalAmount,
                                'payment_status'       => $paymentStatus,
                                'status'               => $status,
                                'is_lock'              => false,
                            ]
                        );

                        if (!$isCurrentMonth) {
                            Payment::updateOrCreate(
                                [
                                    'booking_id' => $booking->id,
                                    'reference'  => sprintf('PAY-%s-%s', $car->id, $start->format('Ym')),
                                ],
                                [
                                    'amount'  => $totalAmount,
                                    'status'  => 'Paid',
                                    'method'  => $this->randomPaymentMethod(),
                                    'paid_at' => $actualReturn
                                        ? $actualReturn->copy()->addDay()->setTime(9, random_int(0, 59))
                                        : $end->copy()->addDay()->setTime(9, random_int(0, 59)),
                                    'meta'    => [
                                        'channel' => 'tenant-dashboard-seed',
                                        'note'    => 'Auto-generated settlement',
                                    ],
                                ]
                            );
                        } else {
                            Payment::where('booking_id', $booking->id)->delete();
                        }
                    }
                }

                $monthCursor->addMonth();
            }
        });
    }

    /**
     * Ensure there are borrowers available for bookings.
     */
    protected function ensureBorrowers()
    {
        $borrowerIds = User::where('type', 'borrower')->pluck('id');

        if ($borrowerIds->isNotEmpty()) {
            return $borrowerIds;
        }

        $samples = [
            [
                'first_name'   => 'Alyssa',
                'middle_name'  => 'Marin',
                'last_name'    => 'Cortez',
                'email'        => 'demo.borrower1@clients.test',
                'password'     => bcrypt('BorrowerPass123!'),
                'type'         => 'borrower',
                'role'         => 'client',
                'phone_number' => '+639988001001',
                'address'      => 'Pasig City',
            ],
            [
                'first_name'   => 'Luis',
                'middle_name'  => 'R.',
                'last_name'    => 'Ramirez',
                'email'        => 'demo.borrower2@clients.test',
                'password'     => bcrypt('BorrowerPass123!'),
                'type'         => 'borrower',
                'role'         => 'client',
                'phone_number' => '+639988001002',
                'address'      => 'Makati City',
            ],
            [
                'first_name'   => 'Gelo',
                'middle_name'  => 'Diaz',
                'last_name'    => 'Castro',
                'email'        => 'demo.borrower3@clients.test',
                'password'     => bcrypt('BorrowerPass123!'),
                'type'         => 'borrower',
                'role'         => 'client',
                'phone_number' => '+639988001003',
                'address'      => 'Quezon City',
            ],
            [
                'first_name'   => 'Siena',
                'middle_name'  => 'Group',
                'last_name'    => 'Accounts',
                'email'        => 'demo.borrower4@clients.test',
                'password'     => bcrypt('BorrowerPass123!'),
                'type'         => 'borrower',
                'role'         => 'client',
                'phone_number' => '+639988001004',
                'address'      => 'Cebu City',
            ],
        ];

        foreach ($samples as $sample) {
            User::updateOrCreate(['email' => $sample['email']], $sample);
        }

        return User::where('type', 'borrower')->pluck('id');
    }

    protected function randomPaymentMethod(): string
    {
        return collect(['cash', 'bank-transfer', 'credit-card', 'gcash'])->random();
    }
}
