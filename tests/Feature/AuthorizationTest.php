<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_users_index_is_admin_only()
    {
        // Non-admin should get 403
        $member = User::first();
        $member->role = 'member';
        $member->save();
        Sanctum::actingAs($member);

        $this->getJson('/api/users')->assertStatus(403);

        // Admin should get 200
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/users')
            ->assertStatus(200)
            ->assertJsonStructure(['status', 'message', 'data', 'links', 'meta']);
    }
}

