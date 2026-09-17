<?php

namespace App\Services\SmmPricing;

use App\Models\Organization;
use App\Models\SmmService;
use App\Models\User;

class SmmPricingEngine
{
    /**
     * Supported currencies and backend exchange rates relative to USD
     */
    protected static array $exchangeRates = [
        'USD' => 1.00,
        'ETB' => 125.00, // 1 USD = 125 ETB
        'EUR' => 0.92,   // 1 USD = 0.92 EUR
        'GBP' => 0.78,   // 1 USD = 0.78 GBP
    ];

    /**
     * Get effective rate per 1k for a user/tenant
     */
    public function getRateForUser(SmmService $service, ?User $user = null, ?Organization $tenant = null): float
    {
        return $service->getEffectiveRate($user, $tenant);
    }

    /**
     * Calculate charge for an SMM order given service, quantity, user, and tenant
     */
    public function calculateOrderPricing(
        SmmService $service,
        int $quantity,
        ?User $user = null,
        ?Organization $tenant = null,
        string $targetCurrency = 'USD'
    ): array {
        // 1. Get rate per 1,000 in USD
        $ratePerKUsd = $service->getEffectiveRate($user, $tenant);
        $costPerKUsd = (float)$service->cost_per_k;

        // 2. Calculate base USD charge and cost
        $totalChargeUsd = round(($quantity / 1000) * $ratePerKUsd, 4);
        $totalCostUsd = round(($quantity / 1000) * $costPerKUsd, 4);
        $totalProfitUsd = round($totalChargeUsd - $totalCostUsd, 4);

        // 3. Convert to target currency if needed
        $rate = self::$exchangeRates[$targetCurrency] ?? 1.00;
        $totalCharge = round($totalChargeUsd * $rate, 4);
        $ratePerK = round($ratePerKUsd * $rate, 4);

        return [
            'quantity' => $quantity,
            'rate_per_k' => $ratePerK,
            'rate_per_k_usd' => $ratePerKUsd,
            'total_charge' => $totalCharge,
            'total_charge_usd' => $totalChargeUsd,
            'total_cost_usd' => $totalCostUsd,
            'total_profit_usd' => $totalProfitUsd,
            'currency' => $targetCurrency,
        ];
    }

    /**
     * Convert amount from source currency to target currency
     */
    public static function convert(float $amount, string $from = 'USD', string $to = 'USD'): float
    {
        if ($from === $to) {
            return $amount;
        }

        $fromRate = self::$exchangeRates[$from] ?? 1.00;
        $toRate = self::$exchangeRates[$to] ?? 1.00;

        // Convert to USD first, then to target currency
        $inUsd = $amount / $fromRate;
        return round($inUsd * $toRate, 4);
    }

    /**
     * Get all active exchange rates
     */
    public static function getRates(): array
    {
        return self::$exchangeRates;
    }
}
