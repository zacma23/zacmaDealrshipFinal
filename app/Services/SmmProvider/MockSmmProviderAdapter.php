<?php

namespace App\Services\SmmProvider;

use App\Models\SmmOrder;
use App\Models\SmmProvider;

class MockSmmProviderAdapter implements SmmProviderAdapterInterface
{
    protected SmmProvider $provider;

    public function setProvider(SmmProvider $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    public function getBalance(): array
    {
        return [
            'success' => true,
            'balance' => (float)$this->provider->balance,
            'currency' => $this->provider->currency,
            'raw' => ['balance' => $this->provider->balance, 'currency' => $this->provider->currency],
        ];
    }

    public function getServices(): array
    {
        return [
            'success' => true,
            'services' => [
                [
                    'service' => 101,
                    'name' => 'Instagram Followers — High Quality [Instant | 30 Days Refill]',
                    'type' => 'Default',
                    'category' => 'Instagram Followers',
                    'rate' => '0.85',
                    'min' => '100',
                    'max' => '100000',
                    'refill' => true,
                    'cancel' => true,
                ],
                [
                    'service' => 102,
                    'name' => 'Instagram Likes — Instant Speed [Non-Drop | HQ]',
                    'type' => 'Default',
                    'category' => 'Instagram Likes',
                    'rate' => '0.25',
                    'min' => '50',
                    'max' => '200000',
                    'refill' => true,
                    'cancel' => true,
                ],
                [
                    'service' => 103,
                    'name' => 'TikTok Followers — Stable Delivery [30 Days Refill Button]',
                    'type' => 'Default',
                    'category' => 'TikTok Followers',
                    'rate' => '1.40',
                    'min' => '100',
                    'max' => '100000',
                    'refill' => true,
                    'cancel' => true,
                ],
            ],
        ];
    }

    public function createOrder(SmmOrder $order): array
    {
        // Deterministic simulated order ID
        $mockId = 'MOCK-' . rand(100000, 999999);

        // Deduct simulated balance
        $cost = (float)$order->cost;
        if ($this->provider->balance >= $cost) {
            $this->provider->decrement('balance', $cost);
        }

        return [
            'success' => true,
            'provider_order_id' => $mockId,
            'raw' => ['order' => $mockId, 'status' => 'success'],
        ];
    }

    public function getOrderStatus(string $providerOrderId): array
    {
        // Simulated realistic status progression
        return [
            'success' => true,
            'status' => SmmOrder::STATUS_IN_PROGRESS,
            'start_count' => 1250,
            'remains' => 0,
            'charge' => 0.85,
            'raw' => ['order' => $providerOrderId, 'status' => 'In progress'],
        ];
    }

    public function getMultipleOrderStatus(array $providerOrderIds): array
    {
        $orders = [];
        foreach ($providerOrderIds as $id) {
            $orders[$id] = [
                'status' => SmmOrder::STATUS_COMPLETED,
                'start_count' => 1250,
                'remains' => 0,
            ];
        }
        return ['success' => true, 'orders' => $orders];
    }

    public function refillOrder(string $providerOrderId): array
    {
        return [
            'success' => true,
            'refill_id' => 'REFILL-' . rand(10000, 99999),
        ];
    }

    public function cancelOrder(string $providerOrderId): array
    {
        return [
            'success' => true,
        ];
    }
}
