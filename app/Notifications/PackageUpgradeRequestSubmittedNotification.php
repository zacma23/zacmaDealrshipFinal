<?php

namespace App\Notifications;

use App\Models\PackageUpgradeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PackageUpgradeRequestSubmittedNotification extends Notification
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
        $price = number_format((float) $this->upgradeRequest->price, 2);
        $currency = $this->upgradeRequest->currency ?? 'ETB';
        $ref = $this->upgradeRequest->payment_reference ?? 'N/A';

        $mail = (new MailMessage)
            ->subject("Package Upgrade Request Submitted: {$planName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your request to upgrade to the **{$planName}** package has been created.")
            ->line("Amount Due: **{$currency} {$price}**")
            ->line("Payment Reference: **{$ref}**")
            ->line("Payment Gateway: **" . ucfirst($this->upgradeRequest->payment_gateway) . "**");

        if ($this->upgradeRequest->payment_url) {
            $mail->action('Proceed to Payment', $this->upgradeRequest->payment_url);
        }

        return $mail->line("Note: Your current package remains active until payment is received and approved by an administrator.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'package_upgrade_submitted',
            'upgrade_request_id' => $this->upgradeRequest->id,
            'requested_plan' => $this->upgradeRequest->requestedPlan?->name,
            'price' => (float) $this->upgradeRequest->price,
            'currency' => $this->upgradeRequest->currency,
            'payment_reference' => $this->upgradeRequest->payment_reference,
            'payment_url' => $this->upgradeRequest->payment_url,
            'payment_status' => $this->upgradeRequest->payment_status,
            'approval_status' => $this->upgradeRequest->approval_status,
        ];
    }
}

