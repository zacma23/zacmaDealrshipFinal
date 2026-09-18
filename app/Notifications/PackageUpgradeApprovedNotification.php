<?php

namespace App\Notifications;

use App\Models\PackageUpgradeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PackageUpgradeApprovedNotification extends Notification
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
        $limit = $this->upgradeRequest->requestedPlan?->listing_limit ?? 'Unlimited';

        return (new MailMessage)
            ->subject("Package Upgrade Approved: {$planName} Plan is Now Active!")
            ->greeting("Hello {$notifiable->name},")
            ->line("Great news! Your package upgrade request to **{$planName}** has been verified and **approved** by our administrator.")
            ->line("Your new package features and increased listing limit (**{$limit} listings**) are now active.")
            ->action('View Your Dashboard', url('/dashboard'))
            ->line("Thank you for choosing Zacma Marketplace.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'package_upgrade_approved',
            'upgrade_request_id' => $this->upgradeRequest->id,
            'requested_plan' => $this->upgradeRequest->requestedPlan?->name,
            'listing_limit' => $this->upgradeRequest->requestedPlan?->listing_limit,
            'approved_at' => $this->upgradeRequest->approved_at?->toIso8601String(),
            'approved_by' => $this->upgradeRequest->approvedBy?->name,
            'approval_status' => 'approved',
        ];
    }
}

