<?php

namespace Tests\Feature\Mvp;

use App\Models\Category;
use App\Models\Listing;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtendedFeaturesAndV1ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MvpMarketplaceSeeder::class);
    }

    public function test_v1_api_health_endpoint(): void
    {
        $response = $this->getJson('/api/health');
        $response->assertStatus(200)
            ->assertJsonPath('status', 'healthy')
            ->assertJsonPath('version', '1.1.0');
    }

    public function test_can_create_and_query_product_listing_via_v1_api(): void
    {
        $user = User::where('email', 'john@example.com')->first();
        $token = $user->createToken('test-token')->plainTextToken;
        $category = Category::where('type', 'product')->first();

        // Create product listing under /api/v1/listings
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/listings', [
                'type' => Listing::TYPE_PRODUCT,
                'category_id' => $category->id,
                'title' => 'CAT Excavator Filter Kit',
                'description' => 'Genuine heavy duty construction filter set in Addis Ababa.',
                'price' => 35000.00,
                'currency' => 'ETB',
                'city' => 'Addis Ababa',
                'listing_attributes' => [
                    'brand' => 'Caterpillar',
                    'condition' => 'Brand New',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.type', 'product');

        // Check search filter for product
        $searchResponse = $this->getJson('/api/v1/marketplace/listings?type=product');
        $searchResponse->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }

    public function test_multi_gateway_and_billing_cycle_checkout(): void
    {
        $user = User::where('email', 'john@example.com')->first();
        $token = $user->createToken('test-token')->plainTextToken;
        $premiumPlan = SubscriptionPlan::where('slug', 'premium')->first();

        // Telebirr quarterly checkout
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/subscriptions/checkout', [
                'plan_id' => $premiumPlan->id,
                'gateway' => 'telebirr',
                'billing_cycle' => 'quarterly',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('gateway', 'telebirr')
            ->assertJsonPath('billing_cycle', 'quarterly');

        $this->assertNotNull($response->json('checkout_url'));
        $this->assertNotNull($response->json('reference'));

        // CBE yearly checkout
        $responseCbe = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/subscriptions/checkout', [
                'plan_id' => $premiumPlan->id,
                'gateway' => 'cbe',
                'billing_cycle' => 'yearly',
            ]);

        $responseCbe->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('gateway', 'cbe')
            ->assertJsonPath('billing_cycle', 'yearly');
    }

    public function test_buyer_requirement_crm_flow(): void
    {
        $buyer = User::where('email', 'chaltu@example.com')->first();
        $token = $buyer->createToken('test-token')->plainTextToken;

        // Post requirement
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/buyer-requirements', [
                'type' => 'vehicle',
                'title' => 'Need 2020 Hyundai Tucson in Hawassa',
                'description' => 'Automatic transmission, less than 50k km.',
                'city' => 'Hawassa',
                'budget_min' => 3000000.00,
                'budget_max' => 4200000.00,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success');

        $reqId = $response->json('data.id');

        // Public requirements index
        $indexResponse = $this->getJson('/api/v1/buyer-requirements?city=Hawassa');
        $indexResponse->assertStatus(200)
            ->assertJsonPath('status', 'success');

        // Close requirement
        $closeResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/v1/buyer-requirements/{$reqId}/status", [
                'status' => 'fulfilled',
            ]);

        $closeResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'fulfilled');
    }

    public function test_reviews_and_ratings(): void
    {
        $reviewer = User::where('email', 'chaltu@example.com')->first();
        $token = $reviewer->createToken('test-token')->plainTextToken;
        $listing = Listing::where('slug', 'toyota-corolla-executive-2021-addis')->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/listings/{$listing->id}/reviews", [
                'rating' => 5,
                'comment' => 'Great communication and honest seller.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.rating', 5);

        // Get reviews
        $listResponse = $this->getJson("/api/v1/listings/{$listing->id}/reviews");
        $listResponse->assertStatus(200)
            ->assertJsonPath('status', 'success');
        $this->assertGreaterThanOrEqual(1, $listResponse->json('total_reviews'));
    }

    public function test_in_app_messaging(): void
    {
        $buyer = User::where('email', 'abebe@example.com')->first();
        $seller = User::where('email', 'john@example.com')->first();
        $token = $buyer->createToken('test-token')->plainTextToken;
        $listing = Listing::where('user_id', $seller->id)->first();

        // Send message
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/messages/send', [
                'recipient_id' => $seller->id,
                'listing_id' => $listing->id,
                'message' => 'Is this vehicle still available for a test drive tomorrow?',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success');

        // Conversations list
        $convResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/messages/conversations');
        $convResponse->assertStatus(200)
            ->assertJsonPath('status', 'success');

        // Thread
        $threadResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/messages/thread/{$seller->id}");
        $threadResponse->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }

    public function test_business_and_dealer_profile_update(): void
    {
        $user = User::where('email', 'john@example.com')->first();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/profile', [
                'name' => 'John Auto Dealership',
                'account_type' => 'dealer',
                'business_name' => 'Bole Premium Motors',
                'license_number' => 'ET-MOT-2024-9871',
                'city' => 'Addis Ababa',
                'phone' => '+251911223344',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $userProfile = $user->fresh()->profile;
        $this->assertEquals('dealer', $userProfile->account_type);
        $this->assertEquals('Bole Premium Motors', $userProfile->business_name);
    }
}
