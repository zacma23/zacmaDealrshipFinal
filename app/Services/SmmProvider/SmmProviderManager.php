<?php

namespace App\Services\SmmProvider;

use App\Models\SmmOrder;
use App\Models\SmmProvider;
use Exception;
use Illuminate\Support\Facades\Log;

class SmmProviderManager
{
    /**
     * Resolve the appropriate adapter instance for a given SmmProvider
     */
    public function getAdapter(SmmProvider $provider): SmmProviderAdapterInterface
    {
        $adapterType = $provider->adapter_type ?: 'standard_v2';

        $adapter = match ($adapterType) {
            'mock_sandbox' => new MockSmmProviderAdapter(),
            default => new StandardSmmAdapter(),
        };

        return $adapter->setProvider($provider);
    }

    /**
     * Submit order with automatic fallback provider fallback if primary fails
     */
    public function dispatchOrder(SmmOrder $order): array
    {
        $provider = $order->provider;

        if (!$provider || !$provider->isActive()) {
            // Check fallback provider
            if ($provider && $provider->fallbackProvider && $provider->fallbackProvider->isActive()) {
                $provider = $provider->fallbackProvider;
                $order->update(['smm_provider_id' => $provider->id]);
            } else {
                // Fallback to active sandbox provider
                $sandbox = SmmProvider::where('adapter_type', 'mock_sandbox')->where('status', 'active')->first();
                if ($sandbox) {
                    $provider = $sandbox;
                    $order->update(['smm_provider_id' => $provider->id]);
                } else {
                    return [
                        'success' => false,
                        'provider_order_id' => null,
                        'error' => 'No active provider available to fulfill this order.',
                    ];
                }
            }
        }

        $adapter = $this->getAdapter($provider);
        $result = $adapter->createOrder($order);

        // If primary provider fails and fallback exists, attempt fallback
        if (!$result['success'] && $provider->fallbackProvider && $provider->fallbackProvider->isActive()) {
            Log::warning("Primary provider [{$provider->name}] failed for order [{$order->id}], trying fallback [{$provider->fallbackProvider->name}]");
            $fallbackProvider = $provider->fallbackProvider;
            $order->update(['smm_provider_id' => $fallbackProvider->id]);
            $fallbackAdapter = $this->getAdapter($fallbackProvider);
            $result = $fallbackAdapter->createOrder($order);
        }

        return $result;
    }
}
