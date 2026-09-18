<?php

namespace App\Notifications;

use App\Models\PackageUpgradeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PackageUpgradeRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public PackageUpgradeRequest $upgradeRequest,
        public string $reason
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $planName = $this->upgradeRequest->requestedPlan?->name ?? 'Package';
        $currentPlanName = $this->upgradeRequest->currentPlan?->name ?? 'Basic';

        return (new MailMessage)
            ->subject("Update on Your Package Upgrade Request: {$planName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your request to upgrade to the **{$planName}** package was not approved.")
            ->line("Reason provided by administrator:")
            ->line("> {$this->reason}")
            ->line("Your current package (**{$currentPlanName}**) remains active and your account features have not been interrupted.")
            ->line("If you believe this was in error or have questions, please contact our support team.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'package_upgrade_rejected',
            'upgrade_request_id' => $this->upgradeRequest->id,
            'requested_plan' => $this->upgradeRequest->requestedPlan?->name,
            'current_plan' => $this->upgradeRequest->currentPlan?->name ?? 'Basic',
            'reason' => $this->reason,
            'rejected_at' => $this->upgradeRequest->rejected_at?->toIso8601String(),
            'approval_status' => 'rejected',
        ];
    }
}

