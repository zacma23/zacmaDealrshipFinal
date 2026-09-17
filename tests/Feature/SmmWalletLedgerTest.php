<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\SmmCategory;
use App\Models\SmmOrder;
use App\Models\SmmPlatform;
use App\Models\SmmService;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmmWalletLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected WalletService $walletService;
    protected User $customer;
    protected User $admin;
    protected SmmService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->walletService = app(WalletService::class);

        $org = Organization::create([
            'name' => 'Apex Agency',
            'slug' => 'apex-agency',
            'status' => 'active',
        ]);

        $this->customer = User::create([
            'organization_id' => $org->id,
            'name' => 'SMM Client',
            'email' => 'client@apex.com',
            'password' => bcrypt('secret123'),
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@zacma.com',
            'password' => bcrypt('secret123'),
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        $platform = SmmPlatform::create([
            'name' => 'Instagram',
            'slug' => 'instagram',
            'is_active' => true,
        ]);

        $category = SmmCategory::create([
            'smm_platform_id' => $platform->id,
            'name' => 'Followers',
            'slug' => 'followers',
            'is_active' => true,
        ]);

        $this->service = SmmService::create([
            'smm_platform_id' => $platform->id,
            'smm_category_id' => $category->id,
            'name' => 'Test Followers',
            'cost_per_k' => 1.00,
            'customer_price_per_k' => 2.50,
            'reseller_price_per_k' => 1.80,
            'min_quantity' => 10,
            'max_quantity' => 10000,
            'status' => 'active',
        ]);
    }

    public function test_user_wallet_is_automatically_created(): void
    {
        $wallet = $this->customer->getOrCreateWallet();

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals(0.00, (float)$wallet->balance);
        $this->assertEquals('USD', $wallet->currency);
        $this->assertEquals($this->customer->id, $wallet->user_id);
    }

    public function test_atomic_deposit_increases_balance_and_records_transaction(): void
    {
        $transaction = $this->walletService->deposit(
            $this->customer,
            75.50,
            'PAY-TEST-9988',
            'chapa',
            'Testing Chapa gateway deposit'
        );

        $this->customer->refresh();
        $wallet = $this->customer->wallet;

        $this->assertEquals(75.50, (float)$wallet->balance);
        $this->assertEquals(75.50, (float)$wallet->total_deposited);
        $this->assertEquals(0.00, (float)$wallet->total_spent);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => WalletTransaction::TYPE_DEPOSIT,
            'amount' => 75.50,
            'reference' => 'PAY-TEST-9988',
            'status' => WalletTransaction::STATUS_COMPLETED,
        ]);
    }

    public function test_order_charge_deducts_balance_and_updates_totals(): void
    {
        // First fund the wallet
        $this->walletService->deposit($this->customer, 100.00, 'PAY-INIT', 'sandbox');

        // Create a dummy order
        $order = SmmOrder::create([
            'smm_service_id' => $this->service->id,
            'user_id' => $this->customer->id,
            'order_number' => 'ORD-TEST-1001',
            'target' => 'https://instagram.com/testpage',
            'quantity' => 1000,
            'charge' => 25.00,
            'cost' => 10.00,
            'status' => SmmOrder::STATUS_PENDING,
        ]);

        $txn = $this->walletService->chargeOrder($this->customer, 25.00, $order);

        $this->customer->refresh();
        $wallet = $this->customer->wallet;

        $this->assertEquals(75.00, (float)$wallet->balance);
        $this->assertEquals(25.00, (float)$wallet->total_spent);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => WalletTransaction::TYPE_ORDER_CHARGE,
            'amount' => -25.00,
            'smm_order_id' => $order->id,
            'status' => WalletTransaction::STATUS_COMPLETED,
        ]);
    }

    public function test_wallet_prevents_overdraft_with_exception(): void
    {
        $this->walletService->deposit($this->customer, 20.00, 'PAY-20', 'sandbox');

        $order = SmmOrder::create([
            'smm_service_id' => $this->service->id,
            'user_id' => $this->customer->id,
            'order_number' => 'ORD-OVERDRAFT',
            'target' => 'https://tiktok.com/@testuser',
            'quantity' => 5000,
            'charge' => 50.00,
            'cost' => 20.00,
            'status' => SmmOrder::STATUS_PENDING,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Insufficient wallet balance');

        try {
            $this->walletService->chargeOrder($this->customer, 50.00, $order);
        } finally {
            // Assert balance was untouched
            $this->customer->refresh();
            $this->assertEquals(20.00, (float)$this->customer->wallet->balance);
        }
    }

    public function test_order_refund_restores_balance_and_records_audit_trail(): void
    {
        $this->walletService->deposit($this->customer, 100.00, 'PAY-100', 'sandbox');

        $order = SmmOrder::create([
            'smm_service_id' => $this->service->id,
            'user_id' => $this->customer->id,
            'order_number' => 'ORD-REFUND-ME',
            'target' => 'https://youtube.com/watch?v=xyz',
            'quantity' => 2000,
            'charge' => 40.00,
            'cost' => 15.00,
            'status' => SmmOrder::STATUS_IN_PROGRESS,
        ]);

        $this->walletService->chargeOrder($this->customer, 40.00, $order);
        $this->assertEquals(60.00, (float)$this->customer->fresh()->wallet->balance);

        // Process refund
        $this->walletService->refundOrder($this->customer, 40.00, $order, 'Remote service unavailable');

        $this->customer->refresh();
        $this->assertEquals(100.00, (float)$this->customer->wallet->balance);
        $this->assertEquals(40.00, (float)$this->customer->wallet->total_refunded);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $this->customer->wallet->id,
            'type' => WalletTransaction::TYPE_REFUND,
            'amount' => 40.00,
            'smm_order_id' => $order->id,
        ]);
    }

    public function test_manual_admin_credit_and_debit(): void
    {
        $creditTxn = $this->walletService->manualCredit(
            $this->customer,
            50.00,
            'Dispute resolution credit',
            $this->admin
        );

        $this->assertEquals(50.00, (float)$this->customer->fresh()->wallet->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $this->customer->wallet->id,
            'type' => WalletTransaction::TYPE_MANUAL_CREDIT,
            'amount' => 50.00,
        ]);

        $debitTxn = $this->walletService->manualDebit(
            $this->customer,
            15.00,
            'Chargeback fee deduction',
            $this->admin
        );

        $this->assertEquals(35.00, (float)$this->customer->fresh()->wallet->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $this->customer->wallet->id,
            'type' => WalletTransaction::TYPE_MANUAL_DEBIT,
            'amount' => -15.00,
        ]);
    }
}
