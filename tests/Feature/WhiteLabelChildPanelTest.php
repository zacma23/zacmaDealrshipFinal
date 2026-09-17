<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\SmmCategory;
use App\Models\SmmOrder;
use App\Models\SmmPlatform;
use App\Models\SmmProvider;
use App\Models\SmmService;
use App\Models\User;
use App\Services\SmmOrder\SmmOrderEngine;
use App\Services\SmmPricing\SmmPricingEngine;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhiteLabelChildPanelTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $childOrg;
    protected User $reseller;
    protected User $childCustomer;
    protected SmmService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->childOrg = Organization::create([
            'name' => 'GrowthBoost SMM',
            'slug' => 'growthboost-smm',
            'brand_name' => 'GrowthBoost Premium',
            'custom_domain' => 'panel.growthboost.com',
            'custom_domain_status' => 'active',
            'default_markup' => 25.0, // 25% markup
            'markup_type' => 'percentage',
            'theme_config' => ['primary_color' => '#8B5CF6'],
            'contact_details' => ['email' => 'support@growthboost.com'],
            'status' => 'active',
        ]);

        $this->reseller = User::create([
            'organization_id' => $this->childOrg->id,
            'name' => 'GrowthBoost Owner',
            'email' => 'owner@growthboost.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_RESELLER,
            'is_active' => true,
        ]);

        $this->childCustomer = User::create([
            'organization_id' => $this->childOrg->id,
            'name' => 'End Customer',
            'email' => 'client@enduser.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $platform = SmmPlatform::create([
            'name' => 'TikTok',
            'slug' => 'tiktok',
            'icon' => 'fa-brands fa-tiktok',
            'is_active' => true,
        ]);

        $category = SmmCategory::create([
            'smm_platform_id' => $platform->id,
            'name' => 'TikTok Likes',
            'slug' => 'tiktok-likes',
            'is_active' => true,
        ]);

        $provider = SmmProvider::create([
            'name' => 'Mock Sandbox Provider',
            'api_url' => 'https://mock.local',
            'api_key' => 'secret',
            'adapter_type' => 'mock_sandbox',
            'status' => 'active',
            'health_status' => 'healthy',
        ]);

        $this->service = SmmService::create([
            'smm_platform_id' => $platform->id,
            'smm_category_id' => $category->id,
            'smm_provider_id' => $provider->id,
            'provider_service_id' => 'mock-tt-2',
            'name' => 'TikTok Viral Likes',
            'cost_per_k' => 1.00,
            'customer_price_per_k' => 4.00,
            'reseller_price_per_k' => 2.50,
            'min_quantity' => 100,
            'max_quantity' => 50000,
            'status' => 'active',
        ]);
    }

    public function test_pricing_engine_applies_child_panel_markup(): void
    {
        $pricingEngine = app(SmmPricingEngine::class);

        // Standard direct customer rate is $4.00 / 1k
        $directRate = $pricingEngine->getRateForUser($this->service, null);
        $this->assertEquals(4.00, $directRate);

        // Child panel customer gets 25% markup on top of $4.00 => $5.00 / 1k
        $childRate = $pricingEngine->getRateForUser($this->service, $this->childCustomer);
        $this->assertEquals(5.00, $childRate);

        // Reseller himself gets wholesale rate ($2.50)
        $resellerRate = $pricingEngine->getRateForUser($this->service, $this->reseller);
        $this->assertEquals(2.50, $resellerRate);
    }

    public function test_child_panel_order_flow_charges_effective_rate(): void
    {
        $walletService = app(WalletService::class);
        $orderEngine = app(SmmOrderEngine::class);

        // Fund customer wallet with $20.00
        $walletService->deposit($this->childCustomer, 20.00, 'TXN-CLIENT-FUND', 'sandbox');

        // Order 2,000 units. Child panel rate is $5.00/1k => total charge is $10.00
        $order = $orderEngine->placeOrder(
            $this->childCustomer,
            $this->service,
            'https://tiktok.com/@video123',
            2000
        );

        $this->assertEquals(10.00, (float)$order->charge);
        $this->assertEquals(2.00, (float)$order->cost); // 2 * $1.00 provider cost
        $this->assertEquals($this->childOrg->id, $order->organization_id);

        $this->childCustomer->refresh();
        $this->assertEquals(10.00, (float)$this->childCustomer->wallet->balance);
    }

    public function test_child_panel_settings_update(): void
    {
        $this->actingAs($this->reseller);

        $response = $this->post(route('reseller.child-panel.update'), [
            'brand_name' => 'Elite Growth Pro',
            'custom_domain' => 'panel.elitegrowthpro.com',
            'subdomain' => 'elitegrowth',
            'default_markup' => 30.0,
            'markup_type' => 'percentage',
            'primary_color' => '#10B981',
            'support_email' => 'help@elitegrowthpro.com',
            'whatsapp' => '+1987654321',
            'telegram' => '@EliteGrowthBot',
            'allow_public_registration' => '1',
        ]);

        $response->assertSessionHas('success');

        $this->childOrg->refresh();
        $this->assertEquals('Elite Growth Pro', $this->childOrg->brand_name);
        $this->assertEquals('panel.elitegrowthpro.com', $this->childOrg->custom_domain);
        $this->assertEquals(30.0, (float)$this->childOrg->default_markup);
        $this->assertEquals('#10B981', $this->childOrg->theme_config['primary_color']);
        $this->assertEquals('help@elitegrowthpro.com', $this->childOrg->contact_details['email']);
    }
}
