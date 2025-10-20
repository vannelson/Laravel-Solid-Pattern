<?php

namespace App\Console\Commands;

use Database\Seeders\TenantDashboardSampleSeeder;
use Illuminate\Console\Command;

class SeedTenantDashboardSamples extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:seed-dashboard';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed realistic tenant dashboard sample data for demo purposes.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Seeding tenant dashboard sample data...');

        $this->call('db:seed', [
            '--class' => TenantDashboardSampleSeeder::class,
        ]);

        $this->info('Tenant dashboard sample data seeded successfully.');

        return self::SUCCESS;
    }
}
