<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentWebhook;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function handleWebhook(Request $request, string $provider)
    {
        $payload = $request->all();
        $headers = $request->headers->all();

        // 1. Log incoming webhook
        $webhook = PaymentWebhook::create([
            'provider' => $provider,
            'event_type' => $request->header('X-Event-Type', 'payment_status'),
            'payload' => $payload,
            'is_processed' => false,
        ]);

        try {
            $providerInstance = $this->paymentService->getProvider($provider);
            $parsed = $providerInstance->parseWebhook($payload, $headers);

            if ($parsed['verified'] && !empty($parsed['transaction_reference'])) {
                $payment = Payment::where('transaction_reference', $parsed['transaction_reference'])
                    ->orWhere('provider_reference', $parsed['transaction_reference'])
                    ->first();

                if ($payment && $parsed['status'] === Payment::STATUS_VERIFIED) {
                    $this->paymentService->finalizeSuccessfulPayment($payment);
                    $webhook->update(['is_processed' => true]);

                    return response()->json(['status' => 'success', 'message' => 'Payment verified and order confirmed']);
                }
            }

            $webhook->update(['is_processed' => true]);
            return response()->json(['status' => 'ignored', 'message' => 'No state change required']);
        } catch (\Throwable $e) {
            Log::error("Payment webhook failure for [{$provider}]", ['error' => $e->getMessage()]);
            $webhook->update(['error_log' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
