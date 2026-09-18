<?php

namespace Tests\Feature\Mvp;

use App\Models\PackageUpgradeRequest;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\PackageUpgradeApprovedNotification;
use App\Notifications\PackageUpgradePaymentReceivedNotification;
use App\Notifications\PackageUpgradeRejectedNotification;
use App\Notifications\PackageUpgradeRequestSubmittedNotification;
use App\Notifications\SubscriptionActivatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PackageUpgradeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MvpMarketplaceSeeder::class);
    }

    public function test_client_can_request_package_upgrade_with_payment_link(): void
    {
        Notification::fake();

        $user = User::where('email', 'john@example.com')->first();
        $token = $user->createToken('test-token')->plainTextToken;
        $premiumPlan = SubscriptionPlan::where('slug', 'premium')->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/subscriptions/upgrade-request', [
                'plan_id' => $premiumPlan->id,
                'gateway' => 'chapa',
                'billing_cycle' => 'monthly',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'message',
                'checkout_url',
                'reference',
                'upgrade_request' => [
                    'id',
                    'current_plan',
                    'requested_plan',
                    'price',
                    'currency',
                    'billing_cycle',
                    'payment_gateway',
                    'payment_status',
                    'approval_status',
                ],
            ]);

        $requestId = $response->json('upgrade_request.id');
        $upgradeRequest = PackageUpgradeRequest::find($requestId);

        $this->assertNotNull($upgradeRequest);
        $this->assertEquals($user->id, $upgradeRequest->user_id);
        $this->assertEquals($premiumPlan->id, $upgradeRequest->requested_plan_id);
        $this->assertEquals(PackageUpgradeRequest::PAYMENT_PENDING, $upgradeRequest->payment_status);
        $this->assertEquals(PackageUpgradeRequest::APPROVAL_PENDING, $upgradeRequest->approval_status);
        $this->assertEquals(499.00, (float) $upgradeRequest->price);

        // Client quota must remain at Basic (not upgraded yet)
        $this->assertEquals(20, $user->fresh()->getListingLimit());

        // Notification must be dispatched to user
        Notification::assertSentTo($user, PackageUpgradeRequestSubmittedNotification::class);
    }

    public function test_webhook_marks_upgrade_request_paid_without_immediate_activation(): void
    {
        Notification::fake();

        $user = User::where('email', 'john@example.com')->first();
        $token = $user->createToken('test-token')->plainTextToken;
        $premiumPlan = SubscriptionPlan::where('slug', 'premium')->first();

        // 1. Initiate upgrade request
        $initResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/subscriptions/upgrade-request', [
                'plan_id' => $premiumPlan->id,
                'gateway' => 'chapa',
                'billing_cycle' => 'monthly',
            ]);

        $reference = $initResponse->json('reference');
        $requestId = $initResponse->json('upgrade_request.id');

        // 2. Webhook arrives for this payment
        $webhookResponse = $this->postJson('/api/v1/webhooks/payment/chapa', [
            'tx_ref' => $reference,
            'reference' => 'CHAPA-SIM-REC99',
            'status' => 'success',
            'amount' => 499.00,
            'currency' => 'ETB',
        ]);

        $webhookResponse->assertStatus(200);

        // 3. Upgrade request payment status must become 'paid'
        $upgradeRequest = PackageUpgradeRequest::find($requestId);
        $this->assertEquals(PackageUpgradeRequest::PAYMENT_PAID, $upgradeRequest->payment_status);
        $this->assertNotNull($upgradeRequest->paid_at);

        // 4. Approval status must STILL be pending
        $this->assertEquals(PackageUpgradeRequest::APPROVAL_PENDING, $upgradeRequest->approval_status);

        // 5. User package is NOT yet active (admin approval required)
        $this->assertEquals(20, $user->fresh()->getListingLimit());

        // 6. User notified: Payment received – Awaiting Admin Approval
        Notification::assertSentTo($user, PackageUpgradePaymentReceivedNotification::class);
    }

    public function test_super_admin_can_view_and_approve_upgrade_request(): void
    {
        Notification::fake();

        $admin = User::where('email', 'admin@zacma.com')->first();
        $adminToken = $admin->createToken('admin-token')->plainTextToken;

        $user = User::where('email', 'john@example.com')->first();
        $userToken = $user->createToken('user-token')->plainTextToken;
        $premiumPlan = SubscriptionPlan::where('slug', 'premium')->first();

        // Initiate upgrade
        $initResponse = $this->withHeader('Authorization', 'Bearer ' . $userToken)
            ->postJson('/api/v1/subscriptions/upgrade-request', [
                'plan_id' => $premiumPlan->id,
                'gateway' => 'telebirr',
                'billing_cycle' => 'monthly',
            ]);

        $requestId = $initResponse->json('upgrade_request.id');

        // Admin views upgrade requests
        auth()->forgetGuards();
        $this->flushHeaders();
        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->getJson('/api/v1/admin/upgrade-requests');

        $listResponse->assertStatus(200)
            ->assertJsonPath('status', 'success');

        // Admin approves upgrade request
        $approveResponse = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->postJson("/api/v1/admin/upgrade-requests/{$requestId}/approve");

        $approveResponse->assertStatus(200)
            ->assertJsonPath('status', 'success');

        // Upgrade request is now approved
        $upgradeRequest = PackageUpgradeRequest::find($requestId);
        $this->assertEquals(PackageUpgradeRequest::APPROVAL_APPROVED, $upgradeRequest->approval_status);
        $this->assertEquals(PackageUpgradeRequest::PAYMENT_VERIFIED, $upgradeRequest->payment_status);
        $this->assertEquals($admin->id, $upgradeRequest->approved_by);

        // User package is now activated to Premium (50 listings quota)
        $this->assertEquals(50, $user->fresh()->getListingLimit());

        $activeSubscription = Subscription::where('user_id', $user->id)
            ->where('plan_id', $premiumPlan->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->first();

        $this->assertNotNull($activeSubscription);

        // Notifications sent to client
        Notification::assertSentTo($user, PackageUpgradeApprovedNotification::class);
        Notification::assertSentTo($user, SubscriptionActivatedNotification::class);
    }

    public function test_super_admin_can_reject_upgrade_request_with_reason(): void
    {
        Notification::fake();

        $admin = User::where('email', 'admin@zacma.com')->first();
        $adminToken = $admin->createToken('admin-token')->plainTextToken;

        $user = User::where('email', 'john@example.com')->first();
        $userToken = $user->createToken('user-token')->plainTextToken;
        $proPlan = SubscriptionPlan::where('slug', 'pro')->first();

        // Initiate upgrade
        $initResponse = $this->withHeader('Authorization', 'Bearer ' . $userToken)
            ->postJson('/api/v1/subscriptions/upgrade-request', [
                'plan_id' => $proPlan->id,
                'gateway' => 'cbe',
                'billing_cycle' => 'yearly',
            ]);

        $requestId = $initResponse->json('upgrade_request.id');

        // Admin rejects upgrade request with reason
        auth()->forgetGuards();
        $this->flushHeaders();
        $rejectResponse = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->postJson("/api/v1/admin/upgrade-requests/{$requestId}/reject", [
                'reason' => 'Bank deposit slip could not be verified with CBE branch.',
            ]);

        $rejectResponse->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $upgradeRequest = PackageUpgradeRequest::find($requestId);
        $this->assertEquals(PackageUpgradeRequest::APPROVAL_REJECTED, $upgradeRequest->approval_status);
        $this->assertEquals('Bank deposit slip could not be verified with CBE branch.', $upgradeRequest->rejection_reason);
        $this->assertEquals($admin->id, $upgradeRequest->rejected_by);

        // Client remains on current Basic package
        $this->assertEquals(20, $user->fresh()->getListingLimit());

        // Client notified with rejection reason
        Notification::assertSentTo($user, PackageUpgradeRejectedNotification::class);
    }

    public function test_non_admin_cannot_approve_or_reject_upgrade_requests(): void
    {
        $user = User::where('email', 'john@example.com')->first();
        $token = $user->createToken('user-token')->plainTextToken;
        $premiumPlan = SubscriptionPlan::where('slug', 'premium')->first();

        $initResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/subscriptions/upgrade-request', [
                'plan_id' => $premiumPlan->id,
            ]);

        $requestId = $initResponse->json('upgrade_request.id');

        // Non-admin tries to approve
        $responseApprove = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/admin/upgrade-requests/{$requestId}/approve");
        $responseApprove->assertStatus(403);

        // Non-admin tries to reject
        $responseReject = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/admin/upgrade-requests/{$requestId}/reject", [
                'reason' => 'Unauthorized attempt',
            ]);
        $responseReject->assertStatus(403);
    }
}
