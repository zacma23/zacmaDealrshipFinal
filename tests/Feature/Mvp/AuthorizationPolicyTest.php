<?php

namespace Tests\Feature\Mvp;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MvpMarketplaceSeeder::class);
    }

    public function test_user_cannot_edit_another_users_listing(): void
    {
        $owner = User::where('email', 'john@example.com')->first();
        $otherUser = User::where('email', 'abebe@example.com')->first();
        $otherToken = $otherUser->createToken('other')->plainTextToken;

        $listing = Listing::where('user_id', $owner->id)->first();
        $this->assertNotNull($listing);

        $response = $this->withHeader('Authorization', 'Bearer ' . $otherToken)
            ->putJson("/api/listings/{$listing->id}", [
                'title' => 'Malicious update by non-owner',
            ]);

        $response->assertStatus(403);
        $this->assertNotEquals('Malicious update by non-owner', $listing->fresh()->title);
    }

    public function test_user_cannot_delete_another_users_listing(): void
    {
        $owner = User::where('email', 'john@example.com')->first();
        $otherUser = User::where('email', 'abebe@example.com')->first();
        $otherToken = $otherUser->createToken('other')->plainTextToken;

        $listing = Listing::where('user_id', $owner->id)->first();
        $this->assertNotNull($listing);

        $response = $this->withHeader('Authorization', 'Bearer ' . $otherToken)
            ->deleteJson("/api/listings/{$listing->id}");

        $response->assertStatus(403);
        $this->assertNull($listing->fresh()->deleted_at);
    }

    public function test_super_admin_can_edit_or_delete_any_listing(): void
    {
        $admin = User::where('email', 'admin@zacma.com')->first();
        $adminToken = $admin->createToken('admin')->plainTextToken;

        $owner = User::where('email', 'john@example.com')->first();
        $listing = Listing::where('user_id', $owner->id)->first();

        // Admin can update
        $resUpdate = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->putJson("/api/listings/{$listing->id}", [
                'title' => 'Admin Moderated Title',
            ]);

        $resUpdate->assertStatus(200);
        $this->assertEquals('Admin Moderated Title', $listing->fresh()->title);
    }
}

