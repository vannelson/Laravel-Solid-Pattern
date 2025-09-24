<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthAndRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        // Run full seeders so we have known users and domain data
        $this->artisan('db:seed');
    }

    public function test_login_is_public_and_throttled()
    {
        // Login with valid seeded credentials should succeed
        $response = $this->postJson('/api/login', [
            'email' => 'vannelsonsimumbay@gmail.com.com',
            'password' => 'admin123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'data' => ['user', 'token']]);

        // Exceed throttle with wrong password attempts (limit is 10/min)
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/login', [
                'email' => 'vannelsonsimumbay@gmail.com.com',
                'password' => 'wrong',
            ]);
        }

        $blocked = $this->postJson('/api/login', [
            'email' => 'vannelsonsimumbay@gmail.com.com',
            'password' => 'wrong',
        ]);

        $blocked->assertStatus(429);
    }
}

