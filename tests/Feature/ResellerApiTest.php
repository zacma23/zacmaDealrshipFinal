<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\ResellerApiKey;
use App\Models\SmmCategory;
use App\Models\SmmPlatform;
use App\Models\SmmProvider;
use App\Models\SmmService;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResellerApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $reseller;
    protected string $plainApiKey;
    protected SmmService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::create([
            'name' => 'Reseller Agency Elite',
            'slug' => 'reseller-agency-elite',
            'status' => 'active',
        ]);

        $this->reseller = User::create([
            'organization_id' => $org->id,
            'name' => 'Agency Boss',
            'email' => 'boss@agencyelite.com',
            'password' => bcrypt('secret123'),
            'role' => User::ROLE_RESELLER,
            'is_active' => true,
        ]);

        $keyGen = ResellerApiKey::generateForUser($this->reseller, 'Production API');
        $this->plainApiKey = $keyGen['plainTextKey'];

        $platform = SmmPlatform::create([
            'name' => 'YouTube',
            'slug' => 'youtube',
            'icon' => 'fa-brands fa-youtube',
            'is_active' => true,
        ]);

        $category = SmmCategory::create([
            'smm_platform_id' => $platform->id,
            'name' => 'YouTube Views',
            'slug' => 'youtube-views',
            'is_active' => true,
        ]);

        $provider = SmmProvider::create([
            'name' => 'Mock Provider',
            'api_url' => 'https://mock.local/api',
            'api_key' => 'secret',
            'adapter_type' => 'mock_sandbox',
            'status' => 'active',
            'health_status' => 'healthy',
        ]);

        $this->service = SmmService::create([
            'smm_platform_id' => $platform->id,
            'smm_category_id' => $category->id,
            'smm_provider_id' => $provider->id,
            'provider_service_id' => 'mock-yt-10',
            'name' => 'YouTube High Retention Views',
            'cost_per_k' => 1.00,
            'customer_price_per_k' => 2.50,
            'reseller_price_per_k' => 1.80,
            'min_quantity' => 500,
            'max_quantity' => 100000,
            'has_refill' => true,
            'has_cancel' => true,
            'status' => 'active',
        ]);
    }

    public function test_api_rejects_invalid_api_key(): void
    {
        $response = $this->postJson('/api/v2', [
            'key' => 'INVALID_TOKEN_123',
            'action' => 'balance',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'error' => 'Invalid or inactive API key',
        ]);
    }

    public function test_api_balance_action_returns_reseller_balance(): void
    {
        $walletService = app(WalletService::class);
        $walletService->deposit($this->reseller, 150.00, 'PAY-KEY', 'sandbox');

        $response = $this->postJson('/api/v2', [
            'key' => $this->plainApiKey,
            'action' => 'balance',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'balance' => '150.00',
            'currency' => 'USD',
        ]);
    }

    public function test_api_services_action_returns_active_catalog_with_reseller_rate(): void
    {
        $response = $this->postJson('/api/v2', [
            'key' => $this->plainApiKey,
            'action' => 'services',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);

        $first = $data[0];
        $this->assertEquals($this->service->id, $first['service']);
        $this->assertEquals('YouTube High Retention Views', $first['name']);
        // Verify rate returned is the wholesale reseller rate ($1.80)
        $this->assertEquals('1.8000', $first['rate']);
        $this->assertEquals(500, $first['min']);
        $this->assertEquals(100000, $first['max']);
    }

    public function test_api_add_order_and_status_check(): void
    {
        $walletService = app(WalletService::class);
        $walletService->deposit($this->reseller, 50.00, 'PAY-PRELOAD', 'sandbox');

        // Place order for 1,000 views at wholesale $1.80
        $addResponse = $this->postJson('/api/v2', [
            'key' => $this->plainApiKey,
            'action' => 'add',
            'service' => $this->service->id,
            'link' => 'https://youtube.com/watch?v=mockvideo',
            'quantity' => 1000,
        ]);

        $addResponse->assertStatus(200);
        $orderData = $addResponse->json();
        $this->assertArrayHasKey('order', $orderData);

        $orderId = $orderData['order'];

        // Balance should be $50.00 - $1.80 = $48.20
        $this->reseller->refresh();
        $this->assertEquals(48.20, (float)$this->reseller->wallet->balance);

        // Check single status
        $statusResponse = $this->postJson('/api/v2', [
            'key' => $this->plainApiKey,
            'action' => 'status',
            'order' => $orderId,
        ]);

        $statusResponse->assertStatus(200);
        $statusData = $statusResponse->json();
        $this->assertArrayHasKey('status', $statusData);
        $this->assertEquals('1.80', $statusData['charge']);
        $this->assertEquals('USD', $statusData['currency']);
    }
}
