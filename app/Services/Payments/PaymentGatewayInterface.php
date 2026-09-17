<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Initialize payment with gateway and return checkout details (e.g. checkout_url).
     */
    public function initializePayment(Payment $payment, array $customerData, string $returnUrl, string $callbackUrl): array;

    /**
     * Verify payment status directly with the gateway.
     */
    public function verifyPayment(string $reference): array;

    /**
     * Verify the authenticity of a webhook request.
     */
    public function verifyWebhookSignature(Request $request): bool;
}

