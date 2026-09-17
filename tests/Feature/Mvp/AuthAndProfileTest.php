<?php

namespace Tests\Feature\Mvp;

use App\Models\Category;
use App\Models\Listing;
use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MvpMarketplaceSeeder::class);
    }

    public function test_new_user_can_register_and_profile_is_auto_created(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Kenenisa Bekele',
            'email' => 'kenenisa@example.com',
            'phone' => '+251911998877',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'token',
                'user' => ['id', 'name', 'username', 'email'],
            ]);

        $user = User::where('email', 'kenenisa@example.com')->first();
        $this->assertNotNull($user);

        // Verify profile was auto-created
        $profile = Profile::where('user_id', $user->id)->first();
        $this->assertNotNull($profile);
        $this->assertEquals('Kenenisa Bekele', $profile->name);

        // Verify role was auto-assigned
        $this->assertTrue($user->hasRole(Role::USER));
    }

    public function test_user_can_login_with_email_or_phone(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'login' => 'john@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'token', 'user']);

        // Phone login
        $responsePhone = $this->postJson('/api/auth/login', [
            'login' => '+251911223344',
            'password' => 'password',
        ]);

        $responsePhone->assertStatus(200);
    }

    public function test_public_profile_endpoint_shows_active_listings(): void
    {
        $john = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($john);

        $response = $this->getJson('/api/profile/' . $john->username);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.user.username', $john->username)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'username', 'avatar', 'city', 'is_verified'],
                    'listings' => ['data'],
                ],
            ]);
    }
}

