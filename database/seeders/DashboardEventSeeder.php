<?php

namespace Database\Seeders;

use App\Models\DashboardEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DashboardEventSeeder extends Seeder
{
    public function run(): void
    {
        DashboardEvent::query()->delete();

        $tenant = User::where('email', 'tenant@autofleet.test')->first()
            ?? User::where('type', 'tenant')->first();

        $tenantId = $tenant?->id;

        $events = [
            [
                'occurred_at' => Carbon::create(2025, 10, 13, 10, 24, 0),
                'title' => 'New corporate reservation',
                'description' => '5-day booking confirmed for BMW 530e Hybrid',
                'event_type' => 'success',
            ],
            [
                'occurred_at' => Carbon::create(2025, 10, 13, 9, 15, 0),
                'title' => 'Payment collected',
                'description' => '₱690 partial payment from Siena Group',
                'event_type' => 'info',
            ],
            [
                'occurred_at' => Carbon::create(2025, 10, 12, 17, 45, 0),
                'title' => 'Vehicle returned late',
                'description' => 'Range Rover Sport SE arrived 45 minutes past scheduled return',
                'event_type' => 'warning',
            ],
            [
                'occurred_at' => Carbon::create(2025, 10, 12, 15, 10, 0),
                'title' => 'Maintenance reminder',
                'description' => 'Audi Q7 Quattro is due for quarterly service check',
                'event_type' => 'info',
            ],
            [
                'occurred_at' => Carbon::create(2025, 10, 12, 11, 30, 0),
                'title' => 'Damage waiver approved',
                'description' => 'Claim approved for minor scratch on Lexus RX 350 F Sport',
                'event_type' => 'success',
            ],
        ];

        foreach ($events as $event) {
            DashboardEvent::create(array_merge($event, [
                'tenant_id' => $tenantId,
                'meta' => null,
            ]));
        }
    }
}

