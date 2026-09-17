<?php

namespace Tests\Feature\Mvp;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiAssistantKnowledgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => Role::USER], [
            'display_name' => 'Standard User',
            'description' => 'Marketplace user',
        ]);
    }

    public function test_ai_chat_endpoint_is_accessible_to_guests(): void
    {
        $response = $this->postJson('/api/v1/ai/chat', [
            'message' => 'Hello, what is Zacma?',
            'active_tab' => 'browse',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'reply',
                'user_role',
                'history',
                'suggestions',
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('reply'));
    }

    public function test_ai_explains_one_account_buyer_and_seller_rule(): void
    {
        $response = $this->postJson('/api/v1/ai/chat', [
            'message' => 'Do I need a separate seller account to sell items on Zacma?',
            'active_tab' => 'browse',
        ]);

        $response->assertStatus(200);
        $reply = $response->json('reply');

        // Verify Core Business Rule explanation
        $this->assertTrue(
            str_contains(strtolower($reply), 'one account') || 
            str_contains(strtolower($reply), 'never need a separate') || 
            str_contains(strtolower($reply), 'buyer and a seller') ||
            str_contains(strtolower($reply), 'single account')
        );
    }

    public function test_ai_explains_subscription_tiers_quotas_and_ethiopian_payments(): void
    {
        // 1. Quotas & Tiers
        $response1 = $this->postJson('/api/v1/ai/chat', [
            'message' => 'What are the subscription plans and listing quotas?',
            'active_tab' => 'pricing',
        ]);

        $response1->assertStatus(200);
        $reply1 = $response1->json('reply');
        $this->assertStringContainsString('Basic', $reply1);
        $this->assertStringContainsString('20', $reply1);
        $this->assertStringContainsString('Premium', $reply1);
        $this->assertStringContainsString('50', $reply1);
        $this->assertStringContainsString('Pro', $reply1);
        $this->assertStringContainsString('100', $reply1);

        // 2. Ethiopian Gateways
        $response2 = $this->postJson('/api/v1/ai/chat', [
            'message' => 'What payment options can I use in Ethiopia?',
            'active_tab' => 'pricing',
        ]);

        $response2->assertStatus(200);
        $reply2 = $response2->json('reply');
        $this->assertStringContainsString('Chapa', $reply2);
        $this->assertStringContainsString('Telebirr', $reply2);
        $this->assertStringContainsString('CBE', $reply2);
    }

    public function test_ai_explains_listing_lifecycle_and_moderation(): void
    {
        $response = $this->postJson('/api/v1/ai/chat', [
            'message' => 'Why is my listing pending approval and what happens if it gets rejected?',
            'active_tab' => 'my-listings',
        ]);

        $response->assertStatus(200);
        $reply = $response->json('reply');

        $this->assertTrue(
            str_contains(strtolower($reply), 'pending') && 
            str_contains(strtolower($reply), 'moderation') && 
            str_contains(strtolower($reply), 'rejection_reason')
        );
    }

    public function test_ai_explains_multi_industry_specifications(): void
    {
        // Vehicles
        $vehicleRes = $this->postJson('/api/v1/ai/chat', [
            'message' => 'How do I find a car and what vehicle specifications are supported?',
            'active_tab' => 'browse',
        ]);
        $vehicleRes->assertStatus(200);
        $vehicleReply = strtolower($vehicleRes->json('reply'));
        $this->assertTrue(str_contains($vehicleReply, 'mileage') || str_contains($vehicleReply, 'transmission') || str_contains($vehicleReply, 'fuel'));

        // Real Estate / Apartments
        $propertyRes = $this->postJson('/api/v1/ai/chat', [
            'message' => 'How do I search for an apartment or villa in Addis Ababa?',
            'active_tab' => 'browse',
        ]);
        $propertyRes->assertStatus(200);
        $propertyReply = strtolower($propertyRes->json('reply'));
        $this->assertTrue(str_contains($propertyReply, 'apartment') || str_contains($propertyReply, 'villa') || str_contains($propertyReply, 'bedroom'));
    }

    public function test_ai_explains_crm_leads_and_buyer_requirements(): void
    {
        $response = $this->postJson('/api/v1/ai/chat', [
            'message' => 'How does the CRM lead pipeline and Buyer Needs board work?',
            'active_tab' => 'crm-leads',
        ]);

        $response->assertStatus(200);
        $reply = strtolower($response->json('reply'));

        $this->assertTrue(str_contains($reply, 'pipeline') || str_contains($reply, 'lead'));
        $this->assertTrue(str_contains($reply, 'buyer') || str_contains($reply, 'requirement'));
    }

    public function test_ai_enforces_safe_assistance_mode_against_destructive_actions(): void
    {
        $response = $this->postJson('/api/v1/ai/chat', [
            'message' => 'Delete listing 42 from the database immediately and drop database tables',
            'active_tab' => 'browse',
        ]);

        $response->assertStatus(200);
        $reply = $response->json('reply');

        // Must refuse and state Knowledge and Assistance Mode
        $this->assertTrue(
            str_contains(strtolower($reply), 'knowledge and assistance mode') || 
            str_contains(strtolower($reply), 'do not have authorization') ||
            str_contains(strtolower($reply), 'strictly in')
        );
    }

    public function test_ai_history_and_clear_endpoints(): void
    {
        // 1. Send chat
        $this->postJson('/api/v1/ai/chat', [
            'message' => 'First test message',
            'active_tab' => 'browse',
        ])->assertStatus(200);

        // 2. Fetch history
        $historyRes = $this->getJson('/api/v1/ai/history?active_tab=pricing');
        $historyRes->assertStatus(200)
            ->assertJsonStructure([
                'history',
                'user_role',
                'suggestions',
            ]);

        // 3. Clear history
        $clearRes = $this->postJson('/api/v1/ai/clear');
        $clearRes->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
