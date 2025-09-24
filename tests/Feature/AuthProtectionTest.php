<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class AuthProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_cars_index_requires_authentication()
    {
        // Without token -> 401 Unauthorized
        $this->getJson('/api/cars')->assertStatus(401);

        // With token -> 200 OK
        $user = User::first();
        Sanctum::actingAs($user);

        $this->getJson('/api/cars')
            ->assertStatus(200)
            ->assertJsonStructure(['status', 'message', 'data', 'links', 'meta']);
    }
}

