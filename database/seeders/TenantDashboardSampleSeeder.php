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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantDashboardSampleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $manilaNow = Carbon::now('Asia/Manila');
            $targetYear = $manilaNow->year;
            $previousYear = $targetYear - 1;

            $tenant = User::firstOrCreate(
                ['email' => 'metrics.tenant@autofleet.test'],
                [
                    'first_name'   => 'Kei',
                    'middle_name'  => 'Baltazar',
                    'last_name'    => 'Soriano',
                    'password'     => Hash::make('TenantDemo123!'),
                    'type'         => 'tenant',
                    'role'         => 'manager',
                    'phone_number' => '+639990000321',
                    'address'      => '21F One Corporate Plaza, BGC, Taguig',
                ],
            );

            $otherTenant = User::firstOrCreate(
                ['email' => 'metrics.other@autofleet.test'],
                [
                    'first_name'   => 'Ira',
                    'middle_name'  => 'C.',
                    'last_name'    => 'Velasco',
                    'password'     => Hash::make('TenantDemo123!'),
                    'type'         => 'tenant',
                    'role'         => 'manager',
                    'phone_number' => '+639990000654',
                    'address'      => 'Makati Central Business District',
                ],
            );

            $borrower = User::firstOrCreate(
                ['email' => 'dashboard.borrower@clients.test'],
                [
                    'first_name'   => 'Gelo',
                    'middle_name'  => 'Diaz',
                    'last_name'    => 'Castro',
                    'password'     => Hash::make('BorrowerDemo123!'),
                    'type'         => 'borrower',
                    'role'         => 'client',
                    'phone_number' => '+639170000441',
                    'address'      => 'Salcedo Village, Makati',
                ],
            );

            $metroCompany = Company::firstOrCreate(
                [
                    'user_id' => $tenant->id,
                    'name'    => 'Metro Mobility Fleet',
                ],
                [
                    'address'   => 'Ortigas Center, Pasig City',
                    'industry'  => 'Transport',
                    'is_default'=> true,
                ],
            );

            $airportShuttle = Company::firstOrCreate(
                [
                    'user_id' => $tenant->id,
                    'name'    => 'Airport Shuttle Partners',
                ],
                [
                    'address'   => 'NAIA Terminal 3, Pasay City',
                    'industry'  => 'Travel',
                    'is_default'=> false,
                ],
            );

            $otherFleet = Company::firstOrCreate(
                [
                    'user_id' => $otherTenant->id,
                    'name'    => 'South Fleet Logistics',
                ],
                [
                    'address'   => 'Alabang, Muntinlupa',
                    'industry'  => 'Logistics',
                    'is_default'=> true,
                ],
            );

            $alphaCar = Car::firstOrCreate(
                [
                    'company_id'     => $metroCompany->id,
                    'info_plateNumber'=> 'NQA-4587',
                ],
                [
                    'info_make'              => 'Toyota',
                    'info_model'             => 'Hiace',
                    'info_year'              => 2023,
                    'info_age'               => 2,
                    'info_carType'           => 'Van',
                    'info_vin'               => Str::upper(Str::random(16)),
                    'info_availabilityStatus'=> 'available',
                    'info_location'          => 'Ortigas Depot',
                    'info_mileage'           => 14582,
                    'spcs_seats'             => 12,
                    'spcs_largeBags'         => 4,
                    'spcs_smallBags'         => 6,
                    'spcs_engineSize'        => '2.8L',
                    'spcs_transmission'      => 'Automatic',
                    'spcs_fuelType'          => 'Diesel',
                    'spcs_fuelEfficiency'    => '12 km/L',
                    'features'               => ['WiFi hotspot', 'USB charging'],
                    'profileImage'           => null,
                    'displayImages'          => [],
                ],
            );

            $betaCar = Car::firstOrCreate(
                [
                    'company_id'     => $airportShuttle->id,
                    'info_plateNumber'=> 'NAI-8253',
                ],
                [
                    'info_make'              => 'Hyundai',
                    'info_model'             => 'Staria',
                    'info_year'              => 2024,
                    'info_age'               => 1,
                    'info_carType'           => 'Van',
                    'info_vin'               => Str::upper(Str::random(16)),
                    'info_availabilityStatus'=> 'available',
                    'info_location'          => 'NAIA Terminal 3',
                    'info_mileage'           => 3210,
                    'spcs_seats'             => 10,
                    'spcs_largeBags'         => 4,
                    'spcs_smallBags'         => 4,
                    'spcs_engineSize'        => '2.2L',
                    'spcs_transmission'      => 'Automatic',
                    'spcs_fuelType'          => 'Diesel',
                    'spcs_fuelEfficiency'    => '11 km/L',
                    'features'               => ['Captain seats', 'Ambient lighting'],
                    'profileImage'           => null,
                    'displayImages'          => [],
                ],
            );

            $otherCar = Car::firstOrCreate(
                [
                    'company_id'     => $otherFleet->id,
                    'info_plateNumber'=> 'SFL-9921',
                ],
                [
                    'info_make'              => 'Isuzu',
                    'info_model'             => 'Traviz',
                    'info_year'              => 2022,
                    'info_age'               => 3,
                    'info_carType'           => 'Light Truck',
                    'info_vin'               => Str::upper(Str::random(16)),
                    'info_availabilityStatus'=> 'active',
                    'info_location'          => 'Alabang Hub',
                    'info_mileage'           => 25000,
                    'spcs_seats'             => 3,
                    'spcs_largeBags'         => 0,
                    'spcs_smallBags'         => 0,
                    'spcs_engineSize'        => '2.5L',
                    'spcs_transmission'      => 'Manual',
                    'spcs_fuelType'          => 'Diesel',
                    'spcs_fuelEfficiency'    => '14 km/L',
                    'features'               => ['Cargo rails'],
                    'profileImage'           => null,
                    'displayImages'          => [],
                ],
            );

            $bookingTemplates = [
                [
                    'key'                   => 'Dashboard Demo - Corporate Shuttle',
                    'car'                   => $alphaCar,
                    'status'                => 'Completed',
                    'payment_status'        => 'Paid',
                    'start'                 => Carbon::create($targetYear, 3, 10, 7, 30, 0, 'Asia/Manila'),
                    'end'                   => Carbon::create($targetYear, 3, 15, 19, 0, 0, 'Asia/Manila'),
                    'actual_return'         => Carbon::create($targetYear, 3, 15, 19, 15, 0, 'Asia/Manila'),
                    'base_amount'           => 120000,
                    'extra_payment'         => 12000,
                    'discount'              => 5000,
                    'destination'           => 'Dashboard Demo - Corporate Shuttle',
                    'total_amount'          => 127000,
                    'payment_meta'          => [
                        'reference' => 'PAY-DEMO-001',
                        'paid_at'   => Carbon::create($targetYear, 3, 16, 9, 10, 0, 'Asia/Manila'),
                    ],
                ],
                [
                    'key'                   => 'Dashboard Demo - VIP Airport Run',
                    'car'                   => $betaCar,
                    'status'                => 'Ongoing',
                    'payment_status'        => 'Pending',
                    'start'                 => Carbon::create($targetYear, 10, 1, 4, 0, 0, 'Asia/Manila'),
                    'end'                   => Carbon::create($targetYear, 10, 1, 23, 0, 0, 'Asia/Manila'),
                    'actual_return'         => null,
                    'base_amount'           => 18500,
                    'extra_payment'         => 3500,
                    'discount'              => 0,
                    'destination'           => 'Dashboard Demo - VIP Airport Run',
                    'total_amount'          => 22000,
                    'payment_meta'          => null,
                ],
                [
                    'key'                   => 'Dashboard Demo - Events Shuttle',
                    'car'                   => $alphaCar,
                    'status'                => 'Reserved',
                    'payment_status'        => 'Pending',
                    'start'                 => Carbon::create($targetYear, 12, 28, 8, 0, 0, 'Asia/Manila'),
                    'end'                   => Carbon::create($targetYear, 12, 31, 21, 0, 0, 'Asia/Manila'),
                    'actual_return'         => null,
                    'base_amount'           => 45000,
                    'extra_payment'         => 0,
                    'discount'              => 2000,
                    'destination'           => 'Dashboard Demo - Events Shuttle',
                    'total_amount'          => 43000,
                    'payment_meta'          => null,
                ],
                [
                    'key'                   => 'Dashboard Demo - Corporate Retreat',
                    'car'                   => $betaCar,
                    'status'                => 'Completed',
                    'payment_status'        => 'Paid',
                    'start'                 => Carbon::create($previousYear, 11, 20, 6, 45, 0, 'Asia/Manila'),
                    'end'                   => Carbon::create($previousYear, 11, 25, 19, 0, 0, 'Asia/Manila'),
                    'actual_return'         => Carbon::create($previousYear, 11, 25, 19, 30, 0, 'Asia/Manila'),
                    'base_amount'           => 98000,
                    'extra_payment'         => 9000,
                    'discount'              => 8000,
                    'destination'           => 'Dashboard Demo - Corporate Retreat',
                    'total_amount'          => 99000,
                    'payment_meta'          => [
                        'reference' => 'PAY-DEMO-002',
                        'paid_at'   => Carbon::create($previousYear, 11, 26, 10, 15, 0, 'Asia/Manila'),
                    ],
                ],
            ];

            foreach ($bookingTemplates as $template) {
                /** @var Car $car */
                $car = $template['car'];
                $booking = Booking::updateOrCreate(
                    [
                        'tenant_id'  => $tenant->id,
                        'company_id' => $car->company_id,
                        'car_id'     => $car->id,
                        'destination'=> $template['destination'],
                    ],
                    [
                        'company_id'          => $car->company_id,
                        'borrower_id'          => $borrower->id,
                        'start_date'           => $template['start']->toDateTimeString(),
                        'end_date'             => $template['end']->toDateTimeString(),
                        'expected_return_date' => $template['end']->toDateTimeString(),
                        'actual_return_date'   => $template['actual_return']?->toDateTimeString(),
                        'rate'                 => 2500,
                        'rate_type'            => 'daily',
                        'base_amount'          => $template['base_amount'],
                        'extra_payment'        => $template['extra_payment'],
                        'discount'             => $template['discount'],
                        'total_amount'         => $template['total_amount'],
                        'payment_status'       => $template['payment_status'],
                        'status'               => $template['status'],
                        'is_lock'              => false,
                    ],
                );

                if ($template['payment_meta'] !== null) {
                    $paymentMeta = $template['payment_meta'];

                    Payment::updateOrCreate(
                        [
                            'booking_id' => $booking->id,
                            'reference'  => $paymentMeta['reference'],
                        ],
                        [
                            'amount'    => $template['total_amount'],
                            'status'    => 'Paid',
                            'method'    => 'bank-transfer',
                            'paid_at'   => $paymentMeta['paid_at']->toDateTimeString(),
                            'meta'      => [
                                'channel' => 'dashboard-demo',
                                'note'    => 'Seeded sample payment',
                            ],
                        ],
                    );

                    $booking->update(['payment_status' => 'Paid']);
                }
            }

            // Ensure isolation data for other tenant for testing purposes.
            Booking::updateOrCreate(
                [
                    'tenant_id'  => $otherTenant->id,
                    'company_id' => $otherCar->company_id,
                    'car_id'     => $otherCar->id,
                    'destination'=> 'Dashboard Demo - South Fleet Contract',
                ],
                [
                    'company_id'          => $otherCar->company_id,
                    'borrower_id'          => $borrower->id,
                    'start_date'           => Carbon::create($targetYear, 4, 4, 8, 0, 0, 'Asia/Manila')->toDateTimeString(),
                    'end_date'             => Carbon::create($targetYear, 4, 8, 18, 0, 0, 'Asia/Manila')->toDateTimeString(),
                    'expected_return_date' => Carbon::create($targetYear, 4, 8, 18, 0, 0, 'Asia/Manila')->toDateTimeString(),
                    'actual_return_date'   => Carbon::create($targetYear, 4, 8, 18, 30, 0, 'Asia/Manila')->toDateTimeString(),
                    'rate'                 => 1800,
                    'rate_type'            => 'daily',
                    'base_amount'          => 45000,
                    'extra_payment'        => 3500,
                    'discount'             => 0,
                    'total_amount'         => 48500,
                    'payment_status'       => 'Paid',
                    'status'               => 'Completed',
                    'is_lock'              => false,
                ],
            );
        });
    }
}
