<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TenantCompanySeeder::class,
            TenantCarInventorySeeder::class,
            CarRateSeeder::class,
            TenantBookingPaymentSeeder::class,
            TenantWalkInBookingSeeder::class,
            TenantHistoricalWalkInBookingSeeder::class,
            TenantHighVolumeWalkInBookingSeeder::class,
            BorrowerUserSeeder::class,
        ]);
    }
}
