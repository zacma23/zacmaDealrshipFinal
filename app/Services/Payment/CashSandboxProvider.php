<?php

namespace App\Services\Payment;

use App\Models\Payment;

class CashSandboxProvider implements PaymentProviderInterface
{
    public function getProviderName(): string
    {
        return Payment::PROVIDER_CASH;
    }

    public function initiatePayment(Payment $payment, array $options = []): array
    {
        return [
            'success' => true,
            'redirect_url' => route('checkout.success', ['reference' => $payment->transaction_reference]),
            'provider_reference' => 'CASH-' . strtoupper(substr(uniqid(), -6)),
            'raw' => ['instructions' => 'Pay cash on delivery, bank transfer, or in-person deposit at dealership office.'],
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
