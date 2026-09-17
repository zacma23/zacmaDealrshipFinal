<?php

namespace App\Services\SmmProvider;

use App\Models\SmmOrder;
use App\Models\SmmProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StandardSmmAdapter implements SmmProviderAdapterInterface
{
    protected SmmProvider $provider;

    public function setProvider(SmmProvider $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    public function getBalance(): array
    {
        try {
            $response = Http::timeout(10)->asForm()->post($this->provider->api_url, [
                'key' => $this->provider->api_key,
                'action' => 'balance',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['balance'])) {
                    $balance = (float)$data['balance'];
                    $currency = $data['currency'] ?? 'USD';

                    $this->provider->update([
                        'balance' => $balance,
                        'currency' => $currency,
                        'health_status' => 'healthy',
                    ]);

                    return [
                        'success' => true,
                        'balance' => $balance,
                        'currency' => $currency,
                        'raw' => $data,
                    ];
                }
            }

            return [
                'success' => false,
                'balance' => 0.0,
                'currency' => 'USD',
                'error' => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error("SMM Provider getBalance error [{$this->provider->id}]: " . $e->getMessage());
            return ['success' => false, 'balance' => 0.0, 'currency' => 'USD', 'error' => $e->getMessage()];
        }
    }

    public function getServices(): array
    {
        try {
            $response = Http::timeout(20)->asForm()->post($this->provider->api_url, [
                'key' => $this->provider->api_key,
                'action' => 'services',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data)) {
                    $this->provider->update(['last_sync_at' => now(), 'health_status' => 'healthy']);
                    return ['success' => true, 'services' => $data];
                }
            }

            return ['success' => false, 'services' => [], 'error' => $response->body()];
        } catch (\Throwable $e) {
            Log::error("SMM Provider getServices error [{$this->provider->id}]: " . $e->getMessage());
            return ['success' => false, 'services' => [], 'error' => $e->getMessage()];
        }
    }

    public function createOrder(SmmOrder $order): array
    {
        try {
            $service = $order->service;
            $params = [
                'key' => $this->provider->api_key,
                'action' => 'add',
                'service' => $service->provider_service_id,
                'link' => $order->target,
                'quantity' => $order->quantity,
            ];

            if ($order->is_drip_feed && $order->drip_runs && $order->drip_interval) {
                $params['runs'] = $order->drip_runs;
                $params['interval'] = $order->drip_interval;
            }

            $response = Http::timeout(15)->asForm()->post($this->provider->api_url, $params);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['order'])) {
                    return [
                        'success' => true,
                        'provider_order_id' => (string)$data['order'],
                        'raw' => $data,
                    ];
                }

                if (isset($data['error'])) {
                    return [
                        'success' => false,
                        'provider_order_id' => null,
                        'error' => (string)$data['error'],
                        'raw' => $data,
                    ];
                }
            }

            return [
                'success' => false,
                'provider_order_id' => null,
                'error' => 'Invalid response from provider: ' . substr($response->body(), 0, 200),
            ];
        } catch (\Throwable $e) {
            Log::error("SMM Provider createOrder error: " . $e->getMessage());
            return ['success' => false, 'provider_order_id' => null, 'error' => $e->getMessage()];
        }
    }

    public function getOrderStatus(string $providerOrderId): array
    {
        try {
            $response = Http::timeout(10)->asForm()->post($this->provider->api_url, [
                'key' => $this->provider->api_key,
                'action' => 'status',
                'order' => $providerOrderId,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['status'])) {
                    return [
                        'success' => true,
                        'status' => $this->normalizeStatus($data['status']),
                        'start_count' => isset($data['start_count']) ? (int)$data['start_count'] : null,
                        'remains' => isset($data['remains']) ? (int)$data['remains'] : null,
                        'charge' => isset($data['charge']) ? (float)$data['charge'] : null,
                        'raw' => $data,
                    ];
                }
            }

            return ['success' => false, 'status' => 'pending', 'error' => $response->body()];
        } catch (\Throwable $e) {
            return ['success' => false, 'status' => 'pending', 'error' => $e->getMessage()];
        }
    }

    public function getMultipleOrderStatus(array $providerOrderIds): array
    {
        try {
            $response = Http::timeout(15)->asForm()->post($this->provider->api_url, [
                'key' => $this->provider->api_key,
                'action' => 'status',
                'orders' => implode(',', $providerOrderIds),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data)) {
                    $results = [];
                    foreach ($data as $id => $orderData) {
                        if (is_array($orderData) && isset($orderData['status'])) {
                            $results[$id] = [
                                'status' => $this->normalizeStatus($orderData['status']),
                                'start_count' => isset($orderData['start_count']) ? (int)$orderData['start_count'] : null,
                                'remains' => isset($orderData['remains']) ? (int)$orderData['remains'] : null,
                            ];
                        }
                    }
                    return ['success' => true, 'orders' => $results];
                }
            }

            return ['success' => false, 'orders' => []];
        } catch (\Throwable $e) {
            return ['success' => false, 'orders' => [], 'error' => $e->getMessage()];
        }
    }

    public function refillOrder(string $providerOrderId): array
    {
        try {
            $response = Http::timeout(10)->asForm()->post($this->provider->api_url, [
                'key' => $this->provider->api_key,
                'action' => 'refill',
                'order' => $providerOrderId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['refill'])) {
                    return ['success' => true, 'refill_id' => (string)$data['refill']];
                }
            }

            return ['success' => false, 'error' => $response->body()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function cancelOrder(string $providerOrderId): array
    {
        try {
            $response = Http::timeout(10)->asForm()->post($this->provider->api_url, [
                'key' => $this->provider->api_key,
                'action' => 'cancel',
                'order' => $providerOrderId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['cancel'])) {
                    return ['success' => true];
                }
            }

            return ['success' => false, 'error' => $response->body()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function normalizeStatus(string $rawStatus): string
    {
        $s = strtolower(trim($rawStatus));
        return match ($s) {
            'completed', 'complete' => SmmOrder::STATUS_COMPLETED,
            'processing' => SmmOrder::STATUS_PROCESSING,
            'in progress', 'inprogress' => SmmOrder::STATUS_IN_PROGRESS,
            'partial', 'partially completed' => SmmOrder::STATUS_PARTIAL,
            'cancelled', 'canceled' => SmmOrder::STATUS_CANCELLED,
            'failed', 'fail' => SmmOrder::STATUS_FAILED,
            'refunded' => SmmOrder::STATUS_REFUNDED,
            default => SmmOrder::STATUS_PENDING,
        };
    }
}
