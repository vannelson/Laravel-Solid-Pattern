<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSimplePasswordSeeder extends Seeder
{
    public function run(): void
    {
        $plainPassword = 'tenant123';

        User::where('type', 'tenant')->update([
            'password' => Hash::make($plainPassword),
        ]);
    }
}
