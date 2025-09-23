<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Van Nelson Sim P Umbay',
            'email' => 'vannelsonsimumbay@gmail.com.com',
            'password' => Hash::make('admin123'),
        ]);

        User::factory()->count(10)->create(); 
    }
}
