<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->delete();

        // Platform owner
        User::factory()
            ->admin()
            ->create([
                'first_name' => 'Avery',
                'middle_name' => null,
                'last_name' => 'Santos',
                'email' => 'admin@autofleet.test',
                'password' => Hash::make('AdminPass123!'),
                'phone_number' => '+639770000001',
            ]);

        // Primary tenant / fleet manager
        $tenant = User::factory()
            ->tenant()
            ->create([
                'first_name' => 'Mika',
                'middle_name' => 'Reyes',
                'last_name' => 'Valdez',
                'email' => 'tenant@autofleet.test',
                'password' => Hash::make('TenantPass123!'),
                'phone_number' => '+639770000010',
            ]);

        // Additional tenant team members
        User::factory()
            ->tenant()
            ->state(fn () => ['password' => Hash::make('TenantPass123!')])
            ->count(2)
            ->sequence(
                [
                    'first_name' => 'Lara',
                    'middle_name' => 'Mae',
                    'last_name' => 'Go',
                    'email' => 'lara.go@autofleet.test',
                    'phone_number' => '+639770000011',
                ],
                [
                    'first_name' => 'Noel',
                    'middle_name' => 'P',
                    'last_name' => 'Ibanez',
                    'email' => 'noel.ibanez@autofleet.test',
                    'phone_number' => '+639770000012',
                ],
            )
            ->create();

        // Core borrower pool
        User::factory()->count(30)->create();

        // Named borrowers for dashboard demos
        $namedBorrowers = [
            [
                'first_name' => 'Alyssa',
                'middle_name' => 'Marin',
                'last_name' => 'Cortez',
                'email' => 'alyssa.cortez@clients.test',
                'phone_number' => '+639980001230',
            ],
            [
                'first_name' => 'Luis',
                'middle_name' => 'R.',
                'last_name' => 'Ramirez',
                'email' => 'luis.ramirez@clients.test',
                'phone_number' => '+639980001245',
            ],
            [
                'first_name' => 'Siena',
                'middle_name' => 'Group',
                'last_name' => 'Accounts',
                'email' => 'siena.group@clients.test',
                'phone_number' => '+639980001300',
            ],
        ];

        foreach ($namedBorrowers as $borrower) {
            User::factory()->create(array_merge($borrower, [
                'password' => Hash::make('BorrowerPass123!'),
            ]));
        }
    }
}

