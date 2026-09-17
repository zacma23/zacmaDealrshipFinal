<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionActivatedNotification extends Notification
{
    use Queueable;

    public function __construct(public Subscription $subscription)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $planName = $this->subscription->plan?->name ?? 'Premium';
        $limit = $this->subscription->plan?->listing_limit ?? 'Unlimited';

        return (new MailMessage)
            ->subject("Subscription Activated: {$planName} Plan")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your subscription to the **{$planName} Plan** is now active!")
            ->line("Your updated listing quota is now **{$limit} listings**.")
            ->line("Valid until: " . $this->subscription->ends_at->format('M d, Y'))
            ->action('Manage Listings', url('/dashboard/listings'))
            ->line('Thank you for partnering with Zacma Marketplace.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_activated',
            'subscription_id' => $this->subscription->id,
            'plan_id' => $this->subscription->plan_id,
            'plan_name' => $this->subscription->plan?->name,
            'listing_limit' => $this->subscription->plan?->listing_limit,
            'ends_at' => $this->subscription->ends_at->toIso8601String(),
        ];
    }
}

