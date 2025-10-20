<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Car;
use App\Models\Company;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantDashboardSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2024, 4, 15, 9, 0, 0, 'Asia/Manila'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_returns_zero_summary_for_empty_dataset(): void
    {
        $tenant = User::factory()->create([
            'type'     => 'tenant',
            'password' => bcrypt('secret'),
            'name'     => 'Zero Tenant',
        ]);

        Company::create([
            'user_id'    => $tenant->id,
            'name'       => 'Zero Fleet Corp',
            'address'    => 'Pasig City',
            'industry'   => 'Transport',
            'is_default' => true,
        ]);

        Sanctum::actingAs($tenant);

        $response = $this->getJson('/api/tenant/dashboard/summary?year=2024&preset=custom&start_date=2024-01-01&end_date=2024-12-31');

        $response->assertOk();

        $range = $response->json('resolvedRange');
        $this->assertSame('2024-01-01T00:00:00+08:00', $range['start']);
        $this->assertSame('2024-12-31T23:59:59+08:00', $range['end']);
        $this->assertArrayNotHasKey('trend', $response->json());

        $response->assertJson([
            'period' => [
                'year'     => 2024,
                'currency' => 'PHP',
            ],
            'totals' => [
                'annualRevenue'       => 0,
                'bookingsYtd'         => 0,
                'averageBookingValue' => 0,
            ],
            'meta' => [
                'source'          => 'bookings',
                'statusesCounted' => ['Reserved', 'Ongoing', 'Completed'],
            ],
        ]);

        $resolvedRange = $response->json('resolvedRange');
        $this->assertNotEmpty($resolvedRange['start']);
        $this->assertNotEmpty($resolvedRange['end']);
        $this->assertStringContainsString('T', $resolvedRange['start']);

        $this->assertArrayNotHasKey('trend', $response->json());

        $generatedAt = $response->json('meta.generatedAt');
        $this->assertNotEmpty($generatedAt);
        $this->assertStringContainsString('+08:00', $generatedAt);
    }

    public function test_it_aggregates_booking_totals_for_the_selected_year(): void
    {
        [$tenant, $car] = $this->createTenantWithCar();
        $borrower = User::factory()->create([
            'type'     => 'borrower',
            'password' => bcrypt('secret'),
            'name'     => 'Borrower One',
        ]);

        // Completed booking included.
        $this->makeBooking($tenant, $car, $borrower, [
            'destination'        => 'Included Completed',
            'status'             => 'Completed',
            'payment_status'     => 'Paid',
            'start'              => Carbon::create(2024, 2, 1, 8, 0, 0, 'Asia/Manila'),
            'end'                => Carbon::create(2024, 2, 5, 18, 0, 0, 'Asia/Manila'),
            'actual_return'      => Carbon::create(2024, 2, 5, 19, 0, 0, 'Asia/Manila'),
            'total_amount'       => 85000,
        ]);

        // Ongoing booking (actual_return null, falls back to end_date).
        $this->makeBooking($tenant, $car, $borrower, [
            'destination'        => 'Included Ongoing',
            'status'             => 'Ongoing',
            'payment_status'     => 'Pending',
            'start'              => Carbon::create(2024, 7, 1, 9, 0, 0, 'Asia/Manila'),
            'end'                => Carbon::create(2024, 7, 7, 17, 0, 0, 'Asia/Manila'),
            'actual_return'      => null,
            'total_amount'       => 42000,
        ]);

        // Cancelled booking should be excluded.
        $this->makeBooking($tenant, $car, $borrower, [
            'destination'        => 'Excluded Cancelled',
            'status'             => 'Cancelled',
            'payment_status'     => 'Cancelled',
            'start'              => Carbon::create(2024, 3, 1, 8, 0, 0, 'Asia/Manila'),
            'end'                => Carbon::create(2024, 3, 3, 18, 0, 0, 'Asia/Manila'),
            'actual_return'      => Carbon::create(2024, 3, 3, 20, 0, 0, 'Asia/Manila'),
            'total_amount'       => 31000,
        ]);

        // Previous year booking should be excluded.
        $this->makeBooking($tenant, $car, $borrower, [
            'destination'        => 'Previous Year Booking',
            'status'             => 'Completed',
            'payment_status'     => 'Paid',
            'start'              => Carbon::create(2023, 12, 20, 8, 0, 0, 'Asia/Manila'),
            'end'                => Carbon::create(2023, 12, 24, 18, 0, 0, 'Asia/Manila'),
            'actual_return'      => Carbon::create(2023, 12, 24, 18, 30, 0, 'Asia/Manila'),
            'total_amount'       => 50000,
        ]);

        Sanctum::actingAs($tenant);

        $response = $this->getJson('/api/tenant/dashboard/summary?year=2024&preset=custom&start_date=2024-01-01&end_date=2024-12-31');

        $response->assertOk();

        $response->assertJson([
            'totals' => [
                'annualRevenue'       => 127000,
                'bookingsYtd'         => 2,
                'averageBookingValue' => 63500.0,
            ],
            'meta' => [
                'source' => 'bookings',
            ],
        ]);
    }

    public function test_it_uses_payments_when_requested(): void
    {
        [$tenant, $car] = $this->createTenantWithCar();
        $borrower = User::factory()->create([
            'type'     => 'borrower',
            'password' => bcrypt('secret'),
            'name'     => 'Borrower Pay',
        ]);

        $booking = $this->makeBooking($tenant, $car, $borrower, [
            'destination'    => 'Payment Based Booking',
            'status'         => 'Completed',
            'payment_status' => 'Paid',
            'start'          => Carbon::create(2024, 5, 10, 10, 0, 0, 'Asia/Manila'),
            'end'            => Carbon::create(2024, 5, 12, 20, 0, 0, 'Asia/Manila'),
            'actual_return'  => Carbon::create(2024, 5, 12, 21, 30, 0, 'Asia/Manila'),
            'total_amount'   => 76000,
        ]);

        // Paid payment within range.
        Payment::create([
            'booking_id' => $booking->id,
            'amount'     => 55000,
            'status'     => 'Paid',
            'method'     => 'credit-card',
            'reference'  => 'PAY-555',
            'paid_at'    => Carbon::create(2024, 5, 13, 9, 0, 0, 'Asia/Manila')->toDateTimeString(),
        ]);

        // Paid payment outside year should be ignored.
        Payment::create([
            'booking_id' => $booking->id,
            'amount'     => 15000,
            'status'     => 'Paid',
            'method'     => 'credit-card',
            'reference'  => 'PAY-556',
            'paid_at'    => Carbon::create(2023, 12, 31, 23, 59, 0, 'Asia/Manila')->toDateTimeString(),
        ]);

        // Pending payment should be ignored.
        Payment::create([
            'booking_id' => $booking->id,
            'amount'     => 5000,
            'status'     => 'Pending',
            'method'     => 'gcash',
            'reference'  => 'PAY-557',
            'paid_at'    => Carbon::create(2024, 5, 14, 10, 0, 0, 'Asia/Manila')->toDateTimeString(),
        ]);

        Sanctum::actingAs($tenant);

        $response = $this->getJson('/api/tenant/dashboard/summary?year=2024&preset=custom&start_date=2024-01-01&end_date=2024-12-31&use_payments=1');

        $response->assertOk();

        $range = $response->json('resolvedRange');
        $this->assertSame('2024-01-01T00:00:00+08:00', $range['start']);
        $this->assertSame('2024-12-31T23:59:59+08:00', $range['end']);
        $this->assertArrayNotHasKey('trend', $response->json());

        $response->assertJson([
            'totals' => [
                'annualRevenue'       => 55000.0,
                'bookingsYtd'         => 1,
                'averageBookingValue' => 55000.0,
            ],
            'meta' => [
                'source' => 'payments',
            ],
        ]);
    }

    public function test_it_includes_trend_data_when_requested(): void
    {
        [$tenant, $car] = $this->createTenantWithCar();
        $borrower = User::factory()->create([
            'type'     => 'borrower',
            'password' => bcrypt('secret'),
            'name'     => 'Borrower Trend',
        ]);

        // Current period booking (within last 30 days window ending 2024-04-15).
        $this->makeBooking($tenant, $car, $borrower, [
            'destination'    => 'Current Period',
            'status'         => 'Completed',
            'payment_status' => 'Paid',
            'start'          => Carbon::create(2024, 4, 10, 9, 0, 0, 'Asia/Manila'),
            'end'            => Carbon::create(2024, 4, 12, 17, 0, 0, 'Asia/Manila'),
            'actual_return'  => Carbon::create(2024, 4, 12, 19, 0, 0, 'Asia/Manila'),
            'total_amount'   => 30000,
        ]);

        // Previous period booking (immediately prior window).
        $this->makeBooking($tenant, $car, $borrower, [
            'destination'    => 'Previous Period',
            'status'         => 'Completed',
            'payment_status' => 'Paid',
            'start'          => Carbon::create(2024, 2, 25, 9, 0, 0, 'Asia/Manila'),
            'end'            => Carbon::create(2024, 2, 28, 17, 0, 0, 'Asia/Manila'),
            'actual_return'  => Carbon::create(2024, 2, 28, 18, 0, 0, 'Asia/Manila'),
            'total_amount'   => 20000,
        ]);

        Sanctum::actingAs($tenant);

        $response = $this->getJson('/api/tenant/dashboard/summary?year=2024&preset=last_30_days&include_trend=1');

        $response->assertOk();

        $this->assertEquals(30000.0, $response->json('totals.annualRevenue'));
        $trend = $response->json('trend');

        $this->assertNotNull($trend);
        $this->assertEquals(20000.0, $trend['previous']['annualRevenue']);
        $this->assertEquals(1, $trend['previous']['bookingsYtd']);
        $this->assertNotEmpty($trend['previous']['period']['start']);
        $this->assertEquals(50.0, $trend['percentChange']['annualRevenue']);
        $this->assertEquals(0.0, $trend['percentChange']['bookingsYtd']);
        $this->assertEquals(50.0, $trend['percentChange']['averageBookingValue']);
    }

    public function test_it_blocks_access_to_foreign_company(): void
    {
        [$tenant, $car] = $this->createTenantWithCar();
        $otherTenant = User::factory()->create([
            'type'     => 'tenant',
            'password' => bcrypt('secret'),
            'email'    => 'other@fleet.test',
            'name'     => 'Other Tenant',
        ]);

        $foreignCompany = Company::create([
            'user_id'    => $otherTenant->id,
            'name'       => 'Foreign Fleet',
            'address'    => 'Makati',
            'industry'   => 'Transport',
            'is_default' => true,
        ]);

        Sanctum::actingAs($tenant);

        $response = $this->getJson('/api/tenant/dashboard/summary?company_id=' . $foreignCompany->id);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'You are not allowed to access the requested company.',
        ]);
    }

    /**
     * Helper to create a tenant user with a company car ready for bookings.
     *
     * @return array{0:User,1:Car}
     */
    protected function createTenantWithCar(): array
    {
        $tenant = User::factory()->create([
            'type'     => 'tenant',
            'password' => bcrypt('secret'),
            'email'    => 'tenant-' . uniqid() . '@fleet.test',
            'name'     => 'Fleet Tenant',
        ]);

        $company = Company::create([
            'user_id'    => $tenant->id,
            'name'       => 'Metro Demo Fleet ' . uniqid(),
            'address'    => 'Quezon City',
            'industry'   => 'Transport',
            'is_default' => true,
        ]);

        $car = Car::create([
            'company_id'             => $company->id,
            'info_make'              => 'Toyota',
            'info_model'             => 'Innova',
            'info_year'              => 2022,
            'info_age'               => 2,
            'info_carType'           => 'MPV',
            'info_plateNumber'       => 'TEST-' . mt_rand(1000, 9999),
            'info_vin'               => strtoupper(uniqid('VIN')),
            'info_availabilityStatus'=> 'available',
            'info_location'          => 'Quezon City',
            'info_mileage'           => 10000,
            'spcs_seats'             => 7,
            'spcs_largeBags'         => 2,
            'spcs_smallBags'         => 4,
            'spcs_engineSize'        => 2000,
            'spcs_transmission'      => 'Automatic',
            'spcs_fuelType'          => 'Diesel',
            'spcs_fuelEfficiency'    => 12.0,
            'features'               => ['Dashcam', 'WiFi'],
            'profileImage'           => null,
            'displayImages'          => [],
        ]);

        return [$tenant, $car];
    }

    /**
     * Helper to create a booking with the desired attributes.
     *
     * @param User     $tenant
     * @param Car      $car
     * @param User     $borrower
     * @param array<string,mixed> $overrides
     *
     * @return Booking
     */
    protected function makeBooking(User $tenant, Car $car, User $borrower, array $overrides): Booking
    {
        $start = $overrides['start'];
        $end = $overrides['end'];
        $actual = $overrides['actual_return'] ?? null;

        return Booking::create([
            'car_id'               => $car->id,
            'company_id'           => $car->company_id,
            'borrower_id'          => $borrower->id,
            'tenant_id'            => $tenant->id,
            'start_date'           => $start->toDateTimeString(),
            'end_date'             => $end->toDateTimeString(),
            'expected_return_date' => $end->toDateTimeString(),
            'actual_return_date'   => $actual?->toDateTimeString(),
            'destination'          => $overrides['destination'],
            'rate'                 => 2500,
            'rate_type'            => 'daily',
            'base_amount'          => $overrides['total_amount'] ?? 0,
            'extra_payment'        => 0,
            'discount'             => 0,
            'total_amount'         => $overrides['total_amount'],
            'payment_status'       => $overrides['payment_status'],
            'status'               => $overrides['status'],
            'identification_type'  => 'Driver License',
            'identification'       => 'Philippines Driver License',
            'identification_number'=> 'DL-' . uniqid(),
            'renter_first_name'    => 'Walkin',
            'renter_middle_name'   => 'Guest',
            'renter_last_name'     => 'Client',
            'renter_address'       => '123 Example Street, Metro Manila',
            'renter_phone_number'  => '+639123456789',
            'renter_email'         => 'walkin+' . uniqid() . '@demo.test',
            'identification_images'=> [],
        ]);
    }
}



