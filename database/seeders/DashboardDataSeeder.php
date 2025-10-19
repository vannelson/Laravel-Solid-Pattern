<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Convenience seeder for populating all dashboard-related demo data in one go.
 */
class DashboardDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CompanySeeder::class,
            CarSeeder::class,
            CarRateSeeder::class,
            BookingSeeder::class,
            ActivityLogSeeder::class,
            DailyFleetMetricSeeder::class,
        ]);
    }
}
