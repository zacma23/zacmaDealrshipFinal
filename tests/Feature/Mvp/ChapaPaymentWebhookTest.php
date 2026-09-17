<?php

namespace Tests\Feature\Mvp;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\SubscriptionActivatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ChapaPaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MvpMarketplaceSeeder::class);
    }

    public function test_chapa_checkout_initializes_pending_payment(): void
    {
        $user = User::where('email', 'john@example.com')->first();
        $token = $user->createToken('test')->plainTextToken;

        $premiumPlan = SubscriptionPlan::where('slug', 'premium')->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/subscriptions/checkout', [
                'plan_id' => $premiumPlan->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'checkout_url',
                'reference',
                'amount',
                'currency',
            ]);

        $reference = $response->json('reference');
        $payment = Payment::where('transaction_reference', $reference)->first();

        $this->assertNotNull($payment);
        $this->assertEquals($user->id, $payment->user_id);
        $this->assertEquals(Payment::STATUS_PENDING, $payment->status);
        $this->assertEquals(499.00, (float) $payment->amount);
    }

    public function test_webhook_callback_activates_subscription_and_updates_quota(): void
    {
        Notification::fake();

        $user = User::where('email', 'john@example.com')->first();
        $premiumPlan = SubscriptionPlan::where('slug', 'premium')->first();

        $payment = Payment::create([
            'user_id' => $user->id,
            'plan_id' => $premiumPlan->id,
            'provider' => 'chapa',
            'amount' => 499.00,
            'currency' => 'ETB',
            'transaction_reference' => 'TX-ZACMA-TEST12345',
            'status' => Payment::STATUS_PENDING,
        ]);

        $webhookPayload = [
            'tx_ref' => 'TX-ZACMA-TEST12345',
            'reference' => 'CHAPA-PROV-9999',
            'status' => 'success',
            'amount' => 499.00,
            'currency' => 'ETB',
        ];

        $response = $this->postJson('/api/webhooks/payment/chapa', $webhookPayload);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        // Payment status must be completed
        $this->assertEquals(Payment::STATUS_COMPLETED, $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->verified_at);

        // Subscription must be activated
        $subscription = Subscription::where('user_id', $user->id)
            ->where('plan_id', $premiumPlan->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->first();

        $this->assertNotNull($subscription);
        $this->assertTrue($subscription->ends_at > now());

        // Quota updated to 50
        $this->assertEquals(50, $user->fresh()->getListingLimit());

        // Payment appears in history
        $token = $user->createToken('test')->plainTextToken;
        $resHistory = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/payments/history');

        $resHistory->assertStatus(200);
        $this->assertEquals('TX-ZACMA-TEST12345', $resHistory->json('data.data.0.transaction_reference'));

        Notification::assertSentTo($user, SubscriptionActivatedNotification::class);
    }

    public function test_webhook_replay_does_not_double_activate_subscription_idempotency(): void
    {
        Notification::fake();

        $user = User::where('email', 'john@example.com')->first();
        $premiumPlan = SubscriptionPlan::where('slug', 'premium')->first();

        $payment = Payment::create([
            'user_id' => $user->id,
            'plan_id' => $premiumPlan->id,
            'provider' => 'chapa',
            'amount' => 499.00,
            'currency' => 'ETB',
            'transaction_reference' => 'TX-ZACMA-IDEMPOTENT',
            'status' => Payment::STATUS_PENDING,
        ]);

        $payload = [
            'tx_ref' => 'TX-ZACMA-IDEMPOTENT',
            'status' => 'success',
        ];

        // 1st Webhook delivery
        $res1 = $this->postJson('/api/webhooks/payment/chapa', $payload);
        $res1->assertStatus(200)
            ->assertJsonPath('idempotent', false);

        $initialSubCount = Subscription::where('user_id', $user->id)->count();
        $this->assertEquals(1, $initialSubCount);

        // 2nd Webhook replay (Identical payload)
        $res2 = $this->postJson('/api/webhooks/payment/chapa', $payload);
        $res2->assertStatus(200)
            ->assertJsonPath('idempotent', true); // Idempotent response

        // Verify count of subscriptions did NOT increase
        $this->assertEquals(1, Subscription::where('user_id', $user->id)->count());
    }
}
