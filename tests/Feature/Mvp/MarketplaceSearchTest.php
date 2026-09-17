<?php

namespace Tests\Feature\Mvp;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MvpMarketplaceSeeder::class);
    }

    public function test_only_published_listings_appear_in_public_search(): void
    {
        $response = $this->getJson('/api/marketplace/listings');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $listings = $response->json('data.data');
        $this->assertNotEmpty($listings);

        foreach ($listings as $listing) {
            $this->assertEquals('published', $listing['status']);
        }
    }

    public function test_filter_by_type_and_price_range(): void
    {
        $response = $this->getJson('/api/marketplace/listings?type=vehicle&min_price=3000000&max_price=4000000');

        $response->assertStatus(200);
        $data = $response->json('data.data');

        $this->assertNotEmpty($data);
        foreach ($data as $item) {
            $this->assertEquals('vehicle', $item['type']);
            $this->assertGreaterThanOrEqual(3000000, $item['price']);
            $this->assertLessThanOrEqual(4000000, $item['price']);
        }
    }

    public function test_filter_by_city_and_vehicle_attributes(): void
    {
        $response = $this->getJson('/api/marketplace/listings?city=Hawassa&brand=Hyundai');

        $response->assertStatus(200);
        $data = $response->json('data.data');

        $this->assertNotEmpty($data);
        $this->assertEquals('Hawassa', $data[0]['city']);
        $this->assertEquals('Hyundai', $data[0]['listing_attributes']['brand']);
    }

    public function test_filter_by_real_estate_bedrooms(): void
    {
        $response = $this->getJson('/api/marketplace/listings?type=real_estate&bedrooms=4');

        $response->assertStatus(200);
        $data = $response->json('data.data');

        $this->assertNotEmpty($data);
        $this->assertGreaterThanOrEqual(4, $data[0]['bedrooms']);
    }

    public function test_user_can_favorite_and_unfavorite_listings(): void
    {
        $user = User::where('email', 'abebe@example.com')->first();
        $token = $user->createToken('test')->plainTextToken;

        $listing = Listing::where('status', Listing::STATUS_PUBLISHED)->first();

        // 1. Favorite
        $resFav = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/favorites/{$listing->id}/toggle");

        $resFav->assertStatus(200)
            ->assertJsonPath('is_favorited', true);

        // Check favorites list
        $resList = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/favorites');

        $resList->assertStatus(200)
            ->assertJsonCount(1, 'data.data');

        // 2. Unfavorite
        $resUnfav = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/favorites/{$listing->id}/toggle");

        $resUnfav->assertStatus(200)
            ->assertJsonPath('is_favorited', false);
    }
}

