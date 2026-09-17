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
use App\Services\Wallet\WalletService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmmOrderEngineTest extends TestCase
{
    use RefreshDatabase;

    protected SmmOrderEngine $orderEngine;
    protected WalletService $walletService;
    protected User $customer;
    protected SmmService $service;
    protected SmmProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderEngine = app(SmmOrderEngine::class);
        $this->walletService = app(WalletService::class);

        $platform = SmmPlatform::create([
            'name' => 'Instagram',
            'slug' => 'instagram',
            'icon' => 'fa-brands fa-instagram',
            'is_active' => true,
        ]);

        $category = SmmCategory::create([
            'smm_platform_id' => $platform->id,
            'name' => 'Instagram Followers',
            'slug' => 'instagram-followers',
            'is_active' => true,
        ]);

        $this->provider = SmmProvider::create([
            'name' => 'Mock Sandbox Provider',
            'api_url' => 'https://mock-smm-gateway.local/api/v2',
            'api_key' => 'mock-secret-key',
            'adapter_type' => 'mock_sandbox',
            'status' => 'active',
            'health_status' => 'healthy',
        ]);

        $this->service = SmmService::create([
            'smm_platform_id' => $platform->id,
            'smm_category_id' => $category->id,
            'smm_provider_id' => $this->provider->id,
            'provider_service_id' => 'mock-svc-101',
            'name' => 'Instagram High Quality Followers',
            'cost_per_k' => 1.50,
            'customer_price_per_k' => 3.00,
            'reseller_price_per_k' => 2.20,
            'min_quantity' => 100,
            'max_quantity' => 10000,
            'has_refill' => true,
            'refill_days' => 30,
            'has_cancel' => true,
            'status' => 'active',
        ]);

        $this->customer = User::create([
            'name' => 'Jane Buyer',
            'email' => 'jane@buyer.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);
    }

    public function test_order_submission_with_mock_provider_succeeds(): void
    {
        // Give customer $20.00 balance
        $this->walletService->deposit($this->customer, 20.00, 'TXN-PRELOAD', 'sandbox');

        // Order 1,000 units. Rate is $3.00 / 1k => $3.00 total charge
        $order = $this->orderEngine->placeOrder(
            $this->customer,
            $this->service,
            'https://instagram.com/janepicks',
            1000
        );

        $this->assertInstanceOf(SmmOrder::class, $order);
        $this->assertEquals(3.00, (float)$order->charge);
        $this->assertEquals(1.50, (float)$order->cost);
        $this->assertEquals(1000, $order->quantity);
        $this->assertEquals($this->provider->id, $order->smm_provider_id);
        $this->assertNotEmpty($order->provider_order_id);
        $this->assertContains($order->status, [SmmOrder::STATUS_IN_PROGRESS, SmmOrder::STATUS_PROCESSING, SmmOrder::STATUS_PENDING]);

        // Verify balance deducted
        $this->customer->refresh();
        $this->assertEquals(17.00, (float)$this->customer->wallet->balance);
    }

    public function test_order_fails_when_balance_insufficient(): void
    {
        // Give customer only $1.00
        $this->walletService->deposit($this->customer, 1.00, 'TXN-TINY', 'sandbox');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Insufficient wallet balance');

        $this->orderEngine->placeOrder(
            $this->customer,
            $this->service,
            'https://instagram.com/janepicks',
            1000 // costs $3.00
        );
    }

    public function test_order_fails_when_quantity_outside_limits(): void
    {
        $this->walletService->deposit($this->customer, 100.00, 'TXN-FULL', 'sandbox');

        // Below min (min is 100)
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Quantity must be between');

        $this->orderEngine->placeOrder(
            $this->customer,
            $this->service,
            'https://instagram.com/janepicks',
            50
        );
    }

    public function test_sync_order_status_and_cancellation(): void
    {
        $this->walletService->deposit($this->customer, 50.00, 'TXN-TEST', 'sandbox');

        $order = $this->orderEngine->placeOrder(
            $this->customer,
            $this->service,
            'https://instagram.com/syncpage',
            2000 // costs $6.00
        );

        $this->assertEquals(44.00, (float)$this->customer->fresh()->wallet->balance);

        // Sync order status
        $updatedOrder = $this->orderEngine->syncOrderStatus($order);
        $this->assertInstanceOf(SmmOrder::class, $updatedOrder);

        // Cancel and refund order
        $this->orderEngine->failAndRefundOrder($order, 'Admin canceled order');

        $order->refresh();
        $this->assertEquals(SmmOrder::STATUS_CANCELED, $order->status);
        $this->assertEquals(50.00, (float)$this->customer->fresh()->wallet->balance);
    }

    public function test_customer_can_view_new_order_page_with_service_query_parameter(): void
    {
        $response = $this->actingAs($this->customer)->get('/customer/smm/new-order?service_id=' . $this->service->id);

        $response->assertStatus(200);
        $response->assertSee($this->service->name);
        $response->assertSee(route('customer.smm.orders.index'));
    }
}
