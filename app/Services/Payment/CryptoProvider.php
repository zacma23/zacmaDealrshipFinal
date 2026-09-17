<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class CryptoProvider implements PaymentProviderInterface
{
    public function getProviderName(): string
    {
        return Payment::PROVIDER_CRYPTO;
    }

    public function initiatePayment(Payment $payment, array $options = []): array
    {
        $orgId = $payment->organization_id;
        $usdtWallet = Setting::get('crypto_usdt_wallet', 'TYDzsXDvG24t5TwtV9r7DkEpxgJqJ3u5Lp', $orgId);
        $btcWallet = Setting::get('crypto_btc_wallet', 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh', $orgId);

        // Approximate ETB to USDT conversion (1 USD ≈ 125 ETB)
        $rate = 125.0;
        $usdtAmount = round($payment->amount / $rate, 2);

        $payload = [
            'network' => 'USDT (TRC20)',
            'wallet_address' => $usdtWallet,
            'btc_wallet' => $btcWallet,
            'amount_usdt' => $usdtAmount,
            'original_amount' => $payment->amount,
            'original_currency' => $payment->currency,
            'exchange_rate' => "1 USD = {$rate} ETB",
            'tx_reference' => $payment->transaction_reference,
            'instructions' => "Send exactly {$usdtAmount} USDT (TRC-20) to wallet: {$usdtWallet}. Instant block confirmation.",
        ];

        return [
            'success' => true,
            'redirect_url' => route('checkout.success', ['reference' => $payment->transaction_reference]),
            'provider_reference' => 'CRYPTO-' . strtoupper(substr(md5(uniqid()), 0, 10)),
            'raw' => $payload,
        ];
    }

    public function verifyPayment(Payment $payment): bool
    {
        return true;
    }

    public function parseWebhook(array $payload, array $headers = []): array
    {
        return [
            'verified' => true,
            'transaction_reference' => $payload['reference'] ?? null,
            'status' => Payment::STATUS_VERIFIED,
        ];
    }
}
