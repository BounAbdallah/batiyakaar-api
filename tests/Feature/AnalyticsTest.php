<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;


    public function test_visit_can_be_stored()
    {
        $response = $this->postJson('/api/v1/analytics/visit', [
            'page' => 'test-page'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('visits', [
            'page' => 'test-page'
        ]);

        // Cleanup
        Visit::where('page', 'test-page')->delete();
    }

    public function test_user_last_seen_is_updated()
    {
        $user = User::create([
            'nom' => 'Test',
            'prenom' => 'User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'agence', // or whatever valid type
            'actif' => true
        ]);

        Sanctum::actingAs($user);

        // Make a request to a protected route
        $response = $this->getJson('/api/v1/auth/user'); // /auth/user is a simple protected route

        $response->assertStatus(200);

        $user->refresh();
        $this->assertNotNull($user->last_seen_at);

        // Cleanup not needed with RefreshDatabase but good practice if removing trait
    }

    public function test_admin_stats_returns_visits_and_online_users()
    {
        // Create user online
        $user = User::create([
            'nom' => 'Admin',
            'prenom' => 'User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'admin',
            'actif' => true,
            'last_seen_at' => now()
        ]);

        // Create visits
        Visit::create(['page' => 'landing']);
        Visit::create(['page' => 'platform']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/admin/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'users' => ['online', 'online_list'],
                    'visits' => ['landing', 'platform', 'other', 'total']
                ]
            ]);

        // Cleanup
        Visit::where('page', 'landing')->delete();
        Visit::where('page', 'platform')->delete();
    }
}
