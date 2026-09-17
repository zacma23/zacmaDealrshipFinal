<?php

namespace App\Notifications;

use App\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ListingStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Listing $listing,
        public string $status,
        public ?string $reason = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusFormatted = ucfirst($this->status);
        $mail = (new MailMessage)
            ->subject("Listing Update: {$this->listing->title} is {$statusFormatted}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your listing \"{$this->listing->title}\" has been updated to: {$statusFormatted}.");

        if ($this->status === Listing::STATUS_REJECTED && $this->reason) {
            $mail->line("Rejection reason: {$this->reason}")
                 ->line("Please edit and resubmit your listing according to the feedback.");
        } elseif ($this->status === Listing::STATUS_PUBLISHED) {
            $mail->line("Your listing is now live and visible to all buyers on the marketplace!")
                 ->action('View Listing', url('/listings/' . $this->listing->slug));
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'listing_status_updated',
            'listing_id' => $this->listing->id,
            'listing_title' => $this->listing->title,
            'listing_slug' => $this->listing->slug,
            'status' => $this->status,
            'reason' => $this->reason,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}

