<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BorrowerUserSeeder extends Seeder
{
    /**
     * Seed at least five borrower accounts for testing/demo purposes.
     */
    public function run(): void
    {
        $profiles = [
            ['Rafael', 'Cruz', 'Montes', 'rafael.montes@borrowers.test', '+63 917 200 1111', 'Bonifacio Global City, Taguig'],
            ['Ivy', 'Santos', 'Garcia', 'ivy.garcia@borrowers.test', '+63 917 200 2222', 'Makati Central Business District'],
            ['Leo', 'Ramirez', 'Fernandez', 'leo.fernandez@borrowers.test', '+63 917 200 3333', 'Ortigas Center, Pasig'],
            ['Mika', 'Lopez', 'Villanueva', 'mika.villanueva@borrowers.test', '+63 917 200 4444', 'Cebu IT Park, Cebu City'],
            ['Noel', 'Chan', 'Reyes', 'noel.reyes@borrowers.test', '+63 917 200 5555', 'Davao Business District'],
        ];

        foreach ($profiles as [$first, $middle, $last, $email, $phone, $address]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $first,
                    'middle_name' => $middle,
                    'last_name' => $last,
                    'password' => Hash::make('Borrower123!'),
                    'type' => 'borrower',
                    'role' => 'user',
                    'phone_number' => $phone,
                    'address' => $address,
                    'remember_token' => Str::random(10),
                ]
            );
        }
    }
}
