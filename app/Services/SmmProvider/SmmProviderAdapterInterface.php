<?php

namespace App\Services\SmmProvider;

use App\Models\SmmOrder;
use App\Models\SmmProvider;

interface SmmProviderAdapterInterface
{
    public function setProvider(SmmProvider $provider): self;

    /**
     * Fetch remote provider account balance
     * Returns ['success' => bool, 'balance' => float, 'currency' => string, 'raw' => array|string]
     */
    public function getBalance(): array;

    /**
     * Fetch list of all services from remote provider
     * Returns ['success' => bool, 'services' => array, 'error' => ?string]
     */
    public function getServices(): array;

    /**
     * Submit an order to remote provider
     * Returns ['success' => bool, 'provider_order_id' => ?string, 'error' => ?string, 'raw' => array|string]
     */
    public function createOrder(SmmOrder $order): array;

    /**
     * Query status of a single order
     * Returns ['success' => bool, 'status' => string, 'start_count' => ?int, 'remains' => ?int, 'charge' => ?float, 'error' => ?string]
     */
    public function getOrderStatus(string $providerOrderId): array;

    /**
     * Query status of multiple orders in batch
     * Returns ['success' => bool, 'orders' => array<provider_id, array{status, start_count, remains}>]
     */
    public function getMultipleOrderStatus(array $providerOrderIds): array;

    /**
     * Request refill for an order
     * Returns ['success' => bool, 'refill_id' => ?string, 'error' => ?string]
     */
    public function refillOrder(string $providerOrderId): array;

    /**
     * Request cancellation of an order
     * Returns ['success' => bool, 'error' => ?string]
     */
    public function cancelOrder(string $providerOrderId): array;
}
