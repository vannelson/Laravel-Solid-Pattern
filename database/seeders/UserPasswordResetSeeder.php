<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserPasswordResetSeeder extends Seeder
{
    /**
     * Ensure every user shares the same known password for testing/demo.
     */
    public function run(): void
    {
        $hashedPassword = Hash::make('password123');

        User::query()->update([
            'password' => $hashedPassword,
        ]);
    }
}
