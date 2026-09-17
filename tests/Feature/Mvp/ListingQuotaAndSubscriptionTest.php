<?php

namespace Tests\Feature\Mvp;

use App\Models\Category;
use App\Models\Listing;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingQuotaAndSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MvpMarketplaceSeeder::class);
    }

    public function test_user_hitting_plan_listing_limit_is_blocked_with_clear_message(): void
    {
        $user = User::where('email', 'john@example.com')->first();
        $user->listings()->forceDelete();
        $token = $user->createToken('test')->plainTextToken;

        $category = Category::first();

        // Basic plan limit is 20. Let's create 20 listings for John.
        for ($i = 1; $i <= 20; $i++) {
            Listing::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'type' => Listing::TYPE_VEHICLE,
                'title' => "Vehicle #{$i}",
                'slug' => "vehicle-test-{$i}-" . uniqid(),
                'price' => 500000.00,
                'currency' => 'ETB',
                'city' => 'Addis Ababa',
                'status' => Listing::STATUS_PUBLISHED,
            ]);
        }

        $this->assertEquals(20, $user->getActiveListingCount());
        $this->assertFalse($user->canCreateListing());

        // Attempt to create listing #21
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/listings', [
                'type' => Listing::TYPE_VEHICLE,
                'category_id' => $category->id,
                'title' => 'Vehicle #21 Overflow',
                'price' => 600000.00,
                'city' => 'Addis Ababa',
            ]);

        // Must be rejected with HTTP 422 / 403 and clear upgrade message
        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('upgrade_required', true);

        $this->assertStringContainsString('Listing limit reached', $response->json('message'));
    }

    public function test_upgrading_subscription_increases_listing_quota(): void
    {
        $user = User::where('email', 'john@example.com')->first();
        $premiumPlan = SubscriptionPlan::where('slug', 'premium')->first();

        $this->assertEquals(20, $user->getListingLimit());

        // Activate Premium subscription
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $premiumPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
        ]);

        $this->assertEquals(50, $user->fresh()->getListingLimit());
        $this->assertTrue($user->canCreateListing());
    }

    public function test_admin_can_edit_plan_price_and_listing_limit(): void
    {
        $admin = User::where('email', 'admin@zacma.com')->first();
        $adminToken = $admin->createToken('admin')->plainTextToken;

        $basicPlan = SubscriptionPlan::where('slug', 'basic')->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->postJson("/api/admin/plans/{$basicPlan->id}", [
                'listing_limit' => 25,
                'price' => 50.00,
            ]);

        $response->assertStatus(200);
        $this->assertEquals(25, $basicPlan->fresh()->listing_limit);
        $this->assertEquals(50.00, (float)$basicPlan->fresh()->price);
    }
}
