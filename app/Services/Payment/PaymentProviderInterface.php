<?php

namespace App\Services\Payment;

use App\Models\Payment;

interface PaymentProviderInterface
{
    public function getProviderName(): string;

    /**
     * Initiate payment with provider gateway
     *
     * @param Payment $payment
     * @param array $options (return_url, cancel_url, customer_phone, etc.)
     * @return array ['success' => bool, 'redirect_url' => ?string, 'provider_reference' => ?string, 'raw' => array]
     */
    public function initiatePayment(Payment $payment, array $options = []): array;

    /**
     * Server-to-server query to verify payment status with gateway
     *
     * @param Payment $payment
     * @return bool
     */
    public function verifyPayment(Payment $payment): bool;

    /**
     * Parse and verify incoming webhook callback
     *
     * @param array $payload
     * @param array $headers
     * @return array ['verified' => bool, 'transaction_reference' => ?string, 'status' => string]
     */
    public function parseWebhook(array $payload, array $headers = []): array;
}
