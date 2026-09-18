<?php

namespace App\Notifications;

use App\Models\PackageUpgradeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PackageUpgradePaymentReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(public PackageUpgradeRequest $upgradeRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $planName = $this->upgradeRequest->requestedPlan?->name ?? 'Package';
        $currentPlanName = $this->upgradeRequest->currentPlan?->name ?? 'Basic';
        $price = number_format((float) $this->upgradeRequest->price, 2);
        $currency = $this->upgradeRequest->currency ?? 'ETB';

        return (new MailMessage)
            ->subject("Payment Received – Awaiting Admin Approval: {$planName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("We have received your payment of **{$currency} {$price}** for the **{$planName}** package upgrade.")
            ->line("Status: **Payment received – Awaiting Admin Approval**")
            ->line("Your current package (**{$currentPlanName}**) remains active while an administrator verifies your payment and activates your upgrade.")
            ->line("You will receive another notification as soon as your upgrade is approved.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'package_upgrade_payment_received',
            'upgrade_request_id' => $this->upgradeRequest->id,
            'requested_plan' => $this->upgradeRequest->requestedPlan?->name,
            'current_plan' => $this->upgradeRequest->currentPlan?->name ?? 'Basic',
            'price' => (float) $this->upgradeRequest->price,
            'currency' => $this->upgradeRequest->currency,
            'payment_reference' => $this->upgradeRequest->payment_reference,
            'payment_status' => $this->upgradeRequest->payment_status,
            'approval_status' => $this->upgradeRequest->approval_status,
            'status_message' => 'Payment received – Awaiting Admin Approval',
        ];
    }
}

