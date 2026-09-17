<?php

namespace Tests\Feature\Mvp;

use App\Models\CrmLead;
use App\Models\Inquiry;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\InquiryReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InquiryAndCrmLeadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MvpMarketplaceSeeder::class);
    }

    public function test_contact_seller_creates_inquiry_and_crm_lead_and_notifies_seller(): void
    {
        Notification::fake();

        $seller = User::where('email', 'john@example.com')->first();
        $buyer = User::where('email', 'abebe@example.com')->first();
        $buyerToken = $buyer->createToken('buyer')->plainTextToken;

        $listing = Listing::where('user_id', $seller->id)->where('status', Listing::STATUS_PUBLISHED)->first();
        $this->assertNotNull($listing);

        $response = $this->withHeader('Authorization', 'Bearer ' . $buyerToken)
            ->postJson('/api/inquiries', [
                'listing_id' => $listing->id,
                'name' => 'Abebe Bikila',
                'email' => 'abebe@example.com',
                'phone' => '+251922334455',
                'message' => 'Is this vehicle available for test drive in Bole this weekend?',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['inquiry_id', 'lead_id'],
            ]);

        // Verify Inquiry created
        $inquiry = Inquiry::where('listing_id', $listing->id)->first();
        $this->assertNotNull($inquiry);
        $this->assertEquals($buyer->id, $inquiry->user_id);
        $this->assertEquals($seller->id, $inquiry->seller_id);

        // Verify CRM Lead created for seller with status 'New'
        $lead = CrmLead::where('inquiry_id', $inquiry->id)->first();
        $this->assertNotNull($lead);
        $this->assertEquals($seller->id, $lead->user_id);
        $this->assertEquals('New', $lead->status);

        // Verify Seller Notification
        Notification::assertSentTo(
            $seller,
            InquiryReceivedNotification::class,
            function ($notification) use ($inquiry) {
                return $notification->inquiry->id === $inquiry->id;
            }
        );
    }

    public function test_seller_can_view_crm_leads_and_update_stage(): void
    {
        $seller = User::where('email', 'john@example.com')->first();
        $sellerToken = $seller->createToken('seller')->plainTextToken;

        $listing = Listing::where('user_id', $seller->id)->first();

        $lead = CrmLead::create([
            'user_id' => $seller->id,
            'listing_id' => $listing->id,
            'buyer_name' => 'Dawit Kebede',
            'buyer_email' => 'dawit@example.com',
            'buyer_phone' => '+251911333444',
            'message' => 'I would like to negotiate price.',
            'status' => 'New',
        ]);

        // View Leads
        $resList = $this->withHeader('Authorization', 'Bearer ' . $sellerToken)
            ->getJson('/api/crm/leads');

        $resList->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data.data');

        // Update Stage to 'Contacted'
        $resUpdate = $this->withHeader('Authorization', 'Bearer ' . $sellerToken)
            ->patchJson("/api/crm/leads/{$lead->id}/status", [
                'status' => 'Contacted',
            ]);

        $resUpdate->assertStatus(200)
            ->assertJsonPath('data.status', 'Contacted');

        $this->assertEquals('Contacted', $lead->fresh()->status);

        // Update Stage to 'Closed'
        $resClose = $this->withHeader('Authorization', 'Bearer ' . $sellerToken)
            ->patchJson("/api/crm/leads/{$lead->id}/status", [
                'status' => 'Closed',
            ]);

        $resClose->assertStatus(200)
            ->assertJsonPath('data.status', 'Closed');

        $this->assertEquals('Closed', $lead->fresh()->status);
    }

    public function test_user_cannot_inquire_on_their_own_listing(): void
    {
        $seller = User::where('email', 'john@example.com')->first();
        $token = $seller->createToken('seller')->plainTextToken;

        $listing = Listing::where('user_id', $seller->id)->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/inquiries', [
                'listing_id' => $listing->id,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'message' => 'Messaging my own item.',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_user_cannot_view_or_update_another_sellers_crm_leads(): void
    {
        $seller1 = User::where('email', 'john@example.com')->first();
        $seller2 = User::where('email', 'abebe@example.com')->first();
        $token2 = $seller2->createToken('seller2')->plainTextToken;

        $listing1 = Listing::where('user_id', $seller1->id)->first();

        $lead = CrmLead::create([
            'user_id' => $seller1->id,
            'listing_id' => $listing1->id,
            'buyer_name' => 'Private Buyer',
            'buyer_email' => 'private@example.com',
            'message' => 'Private lead',
            'status' => 'New',
        ]);

        // Seller 2 attempts to modify Seller 1's lead
        $response = $this->withHeader('Authorization', 'Bearer ' . $token2)
            ->patchJson("/api/crm/leads/{$lead->id}/status", [
                'status' => 'Contacted',
            ]);

        $response->assertStatus(403);
    }
}

