<?php

namespace Tests\Feature\Mvp;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\ListingStatusUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ListingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MvpMarketplaceSeeder::class);
    }

    public function test_user_can_create_vehicle_real_estate_and_apartment_listings_going_to_pending(): void
    {
        $user = User::where('email', 'john@example.com')->first();
        $token = $user->createToken('test')->plainTextToken;

        $vehicleCat = Category::where('type', 'vehicle')->first();
        $realEstateCat = Category::where('type', 'real_estate')->first();
        $apartmentCat = Category::where('type', 'apartment')->first();

        // 1. Create Vehicle Listing
        $resVehicle = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/listings', [
                'type' => Listing::TYPE_VEHICLE,
                'category_id' => $vehicleCat->id,
                'title' => 'Toyota Vitz 2018 Clean',
                'description' => 'Single owner city car in excellent shape.',
                'price' => 1950000.00,
                'currency' => 'ETB',
                'city' => 'Addis Ababa',
                'year' => 2018,
                'listing_attributes' => [
                    'brand' => 'Toyota',
                    'transmission' => 'Automatic',
                    'fuel_type' => 'Petrol',
                ],
            ]);

        $resVehicle->assertStatus(201)
            ->assertJsonPath('data.status', Listing::STATUS_PENDING)
            ->assertJsonPath('data.type', Listing::TYPE_VEHICLE);

        // 2. Create Real Estate Listing
        $resRealEstate = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/listings', [
                'type' => Listing::TYPE_REAL_ESTATE,
                'category_id' => $realEstateCat->id,
                'title' => 'Ayat G+2 Residential Villa',
                'description' => 'Beautiful villa with 5 bedrooms and spacious compound.',
                'price' => 32000000.00,
                'city' => 'Addis Ababa',
                'bedrooms' => 5,
                'listing_attributes' => [
                    'property_purpose' => 'sale',
                    'bathrooms' => 4,
                ],
            ]);

        $resRealEstate->assertStatus(201)
            ->assertJsonPath('data.status', Listing::STATUS_PENDING)
            ->assertJsonPath('data.type', Listing::TYPE_REAL_ESTATE);

        // 3. Create Apartment Listing
        $resApartment = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/listings', [
                'type' => Listing::TYPE_APARTMENT,
                'category_id' => $apartmentCat->id,
                'title' => 'Kazanchis Luxury Studio Apartment',
                'description' => 'Furnished studio near UN ECA.',
                'price' => 45000.00,
                'city' => 'Addis Ababa',
                'bedrooms' => 1,
                'listing_attributes' => [
                    'furnished' => 'yes',
                    'rent_period' => 'monthly',
                ],
            ]);

        $resApartment->assertStatus(201)
            ->assertJsonPath('data.status', Listing::STATUS_PENDING)
            ->assertJsonPath('data.type', Listing::TYPE_APARTMENT);
    }

    public function test_super_admin_can_approve_listing_and_user_is_notified(): void
    {
        Notification::fake();

        $admin = User::where('email', 'admin@zacma.com')->first();
        $adminToken = $admin->createToken('admin')->plainTextToken;

        $pendingListing = Listing::where('status', Listing::STATUS_PENDING)->first();
        $this->assertNotNull($pendingListing);

        $response = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->postJson("/api/admin/listings/{$pendingListing->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', Listing::STATUS_PUBLISHED);

        $this->assertEquals(Listing::STATUS_PUBLISHED, $pendingListing->fresh()->status);

        Notification::assertSentTo(
            $pendingListing->user,
            ListingStatusUpdatedNotification::class,
            function ($notification) {
                return $notification->status === Listing::STATUS_PUBLISHED;
            }
        );
    }

    public function test_super_admin_can_reject_listing_with_reason_and_user_is_notified(): void
    {
        Notification::fake();

        $admin = User::where('email', 'admin@zacma.com')->first();
        $adminToken = $admin->createToken('admin')->plainTextToken;

        $pendingListing = Listing::where('status', Listing::STATUS_PENDING)->first();
        $this->assertNotNull($pendingListing);

        $response = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->postJson("/api/admin/listings/{$pendingListing->id}/reject", [
                'reason' => 'Please provide clearer photos of vehicle documents and odometer.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', Listing::STATUS_REJECTED)
            ->assertJsonPath('data.rejection_reason', 'Please provide clearer photos of vehicle documents and odometer.');

        $this->assertEquals(Listing::STATUS_REJECTED, $pendingListing->fresh()->status);
        $this->assertNotNull($pendingListing->fresh()->rejection_reason);

        Notification::assertSentTo(
            $pendingListing->user,
            ListingStatusUpdatedNotification::class,
            function ($notification) {
                return $notification->status === Listing::STATUS_REJECTED
                    && str_contains($notification->reason, 'clearer photos');
            }
        );
    }

    public function test_rejecting_listing_requires_reason(): void
    {
        $admin = User::where('email', 'admin@zacma.com')->first();
        $adminToken = $admin->createToken('admin')->plainTextToken;

        $pendingListing = Listing::where('status', Listing::STATUS_PENDING)->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->postJson("/api/admin/listings/{$pendingListing->id}/reject", [
                'reason' => '', // missing reason
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_non_admin_cannot_approve_or_reject_listings(): void
    {
        $regularUser = User::where('email', 'john@example.com')->first();
        $userToken = $regularUser->createToken('user')->plainTextToken;

        $pendingListing = Listing::where('status', Listing::STATUS_PENDING)->first();

        $resApprove = $this->withHeader('Authorization', 'Bearer ' . $userToken)
            ->postJson("/api/admin/listings/{$pendingListing->id}/approve");

        $resApprove->assertStatus(403);

        $resReject = $this->withHeader('Authorization', 'Bearer ' . $userToken)
            ->postJson("/api/admin/listings/{$pendingListing->id}/reject", [
                'reason' => 'Unauthorized attempt',
            ]);

        $resReject->assertStatus(403);
    }
}

