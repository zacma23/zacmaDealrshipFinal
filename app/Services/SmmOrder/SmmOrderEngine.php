<?php

namespace App\Services\SmmOrder;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\SmmOrder;
use App\Models\SmmService;
use App\Models\User;
use App\Services\SmmPricing\SmmPricingEngine;
use App\Services\SmmProvider\SmmProviderManager;
use App\Services\Wallet\WalletService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmmOrderEngine
{
    protected WalletService $walletService;
    protected SmmPricingEngine $pricingEngine;
    protected SmmProviderManager $providerManager;

    public function __construct(
        ?WalletService $walletService = null,
        ?SmmPricingEngine $pricingEngine = null,
        ?SmmProviderManager $providerManager = null
    ) {
        $this->walletService = $walletService ?? new WalletService();
        $this->pricingEngine = $pricingEngine ?? new SmmPricingEngine();
        $this->providerManager = $providerManager ?? new SmmProviderManager();
    }

    /**
     * Submit and process a single SMM order
     */
    public function placeOrder(
        User $user,
        SmmService $service,
        string $target,
        int $quantity,
        array $options = [],
        ?Organization $tenant = null,
        bool $isApiOrder = false,
        ?int $apiKeyId = null
    ): SmmOrder {
        // 1. Validation
        if ($service->status !== 'active') {
            throw new Exception("This service is currently unavailable or in maintenance.");
        }

        $cleanTarget = trim($target);
        if (empty($cleanTarget)) {
            throw new Exception("Please provide a valid target URL or username.");
        }

        // Check min/max quantity
        $min = $service->min_quantity;
        $max = $service->max_quantity;
        if ($quantity < $min || $quantity > $max) {
            throw new Exception("Quantity must be between " . number_format($min) . " and " . number_format($max) . ".");
        }

        // 2. Pricing
        $pricing = $this->pricingEngine->calculateOrderPricing($service, $quantity, $user, $tenant);
        $charge = $pricing['total_charge_usd'];
        $cost = $pricing['total_cost_usd'];
        $profit = $pricing['total_profit_usd'];

        // 3. Check Wallet Balance
        $wallet = $user->getOrCreateWallet();
        if (!$wallet->hasSufficientBalance($charge)) {
            throw new Exception("Insufficient wallet balance. Total cost is $" . number_format($charge, 2) . " but your balance is $" . number_format($wallet->balance, 2) . ".");
        }

        // 4. Create Order and Deduct Balance Atomically
        $order = DB::transaction(function () use (
            $user, $service, $cleanTarget, $quantity, $charge, $cost, $profit, $options, $tenant, $isApiOrder, $apiKeyId
        ) {
            $orderNumber = 'ZAC-' . strtoupper(Str::random(8));

            $order = SmmOrder::create([
                'order_number' => $orderNumber,
                'organization_id' => $tenant?->id ?? $user->organization_id,
                'user_id' => $user->id,
                'smm_service_id' => $service->id,
                'smm_provider_id' => $service->smm_provider_id,
                'target' => $cleanTarget,
                'quantity' => $quantity,
                'charge' => $charge,
                'cost' => $cost,
                'profit' => $profit,
                'currency' => 'USD',
                'status' => SmmOrder::STATUS_PENDING,
                'is_drip_feed' => !empty($options['drip_feed']),
                'drip_runs' => $options['runs'] ?? null,
                'drip_interval' => $options['interval'] ?? null,
                'has_refill' => (bool)($service->has_refill ?? false),
                'refill_status' => 'none',
                'is_api_order' => $isApiOrder,
                'reseller_api_key_id' => $apiKeyId,
                'metadata' => $options,
            ]);

            // Deduct charge from wallet with ledger transaction
            $this->walletService->chargeForOrder($user, $charge, $order);

            AuditLog::log(
                'smm.order_created',
                $order,
                null,
                ['charge' => $charge, 'quantity' => $quantity, 'target' => $cleanTarget],
                $order->organization_id,
                $user->id
            );

            return $order;
        });

        // 5. Dispatch to provider (Synchronous or Queue-safe)
        $this->dispatchOrderToProvider($order);

        return $order->fresh();
    }

    /**
     * Dispatch order to external SMM provider
     */
    public function dispatchOrderToProvider(SmmOrder $order): bool
    {
        try {
            $order->update(['status' => SmmOrder::STATUS_PROCESSING]);

            $result = $this->providerManager->dispatchOrder($order);

            if ($result['success'] && !empty($result['provider_order_id'])) {
                $order->update([
                    'provider_order_id' => $result['provider_order_id'],
                    'status' => SmmOrder::STATUS_IN_PROGRESS,
                    'error_message' => null,
                ]);
                return true;
            }

            // If provider dispatch failed
            $errorMessage = $result['error'] ?? 'Provider rejected order request.';
            $this->failAndRefundOrder($order, $errorMessage);
            return false;
        } catch (\Throwable $e) {
            Log::error("Failed to dispatch order #{$order->order_number}: " . $e->getMessage());
            $this->failAndRefundOrder($order, "Provider connection error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark order failed and automatically refund user wallet atomically
     */
    public function failAndRefundOrder(SmmOrder $order, string $errorMessage, string $status = SmmOrder::STATUS_CANCELLED): void
    {
        if (in_array($order->status, [SmmOrder::STATUS_FAILED, SmmOrder::STATUS_REFUNDED, SmmOrder::STATUS_CANCELLED])) {
            return;
        }

        DB::transaction(function () use ($order, $errorMessage, $status) {
            $order->update([
                'status' => $status,
                'error_message' => $errorMessage,
            ]);

            // Refund 100% of the charge back to customer wallet
            $this->walletService->refundOrder($order, (float)$order->charge, "Order refunded: {$errorMessage}");

            AuditLog::log(
                'smm.order_failed_refunded',
                $order,
                ['status' => 'pending'],
                ['status' => $status, 'refund_amount' => $order->charge, 'reason' => $errorMessage],
                $order->organization_id,
                $order->user_id
            );
        });
    }

    /**
     * Synchronize status of an active order from provider API
     */
    public function syncOrderStatus(SmmOrder $order): SmmOrder
    {
        if (!$order->provider_order_id || !$order->provider) {
            return $order;
        }

        // Do not sync orders that are already terminal
        if (in_array($order->status, [SmmOrder::STATUS_COMPLETED, SmmOrder::STATUS_CANCELLED, SmmOrder::STATUS_REFUNDED, SmmOrder::STATUS_FAILED])) {
            return $order;
        }

        try {
            $adapter = $this->providerManager->getAdapter($order->provider);
            $result = $adapter->getOrderStatus($order->provider_order_id);

            if (!$result['success']) {
                return $order;
            }

            $newStatus = $result['status'];
            $startCount = $result['start_count'] ?? $order->start_count;
            $remains = $result['remains'] ?? $order->remains;

            // Handle partial delivery with partial refund
            if ($newStatus === SmmOrder::STATUS_PARTIAL && $order->status !== SmmOrder::STATUS_PARTIAL) {
                $remainsCount = (int)($remains ?? 0);
                if ($remainsCount > 0 && $order->quantity > 0) {
                    $proratedRefund = round(($remainsCount / $order->quantity) * (float)$order->charge, 4);
                    if ($proratedRefund > 0) {
                        $this->walletService->refundOrder(
                            $order,
                            $proratedRefund,
                            "Partial delivery refund ({$remainsCount} remains of {$order->quantity})"
                        );
                    }
                }
            }

            // Handle cancelled at provider with full refund
            if ($newStatus === SmmOrder::STATUS_CANCELLED && $order->status !== SmmOrder::STATUS_CANCELLED) {
                $this->walletService->refundOrder($order, (float)$order->charge, "Order cancelled by provider");
            }

            $order->update([
                'status' => $newStatus,
                'start_count' => $startCount,
                'remains' => $remains,
            ]);

            return $order->fresh();
        } catch (\Throwable $e) {
            Log::error("Error syncing status for order #{$order->order_number}: " . $e->getMessage());
            return $order;
        }
    }

    /**
     * Submit refill request
     */
    public function requestRefill(SmmOrder $order): array
    {
        if (!$order->canRefill()) {
            throw new Exception("This order is not eligible for refill at this time.");
        }

        if (!$order->provider || !$order->provider_order_id) {
            throw new Exception("Order does not have an active provider reference.");
        }

        $adapter = $this->providerManager->getAdapter($order->provider);
        $result = $adapter->refillOrder($order->provider_order_id);

        if ($result['success']) {
            $order->update(['refill_status' => 'pending']);
            return ['success' => true, 'message' => 'Refill request submitted successfully.'];
        }

        return ['success' => false, 'message' => $result['error'] ?? 'Refill rejected by provider.'];
    }

    /**
     * Request order cancellation
     */
    public function requestCancel(SmmOrder $order): array
    {
        if (!$order->canCancel()) {
            throw new Exception("This order cannot be cancelled in its current status.");
        }

        if ($order->provider && $order->provider_order_id) {
            $adapter = $this->providerManager->getAdapter($order->provider);
            $adapter->cancelOrder($order->provider_order_id);
        }

        DB::transaction(function () use ($order) {
            $order->update(['status' => SmmOrder::STATUS_CANCELLED]);
            $this->walletService->refundOrder($order, (float)$order->charge, "Order cancelled by user request");
        });

        return ['success' => true, 'message' => 'Order cancelled and funds refunded to your wallet.'];
    }
}
