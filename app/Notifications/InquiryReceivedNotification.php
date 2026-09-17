<?php

namespace App\Notifications;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InquiryReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(public Inquiry $inquiry)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $listingTitle = $this->inquiry->listing?->title ?? 'your listing';

        return (new MailMessage)
            ->subject("New Inquiry on: {$listingTitle}")
            ->greeting("Hello {$notifiable->name},")
            ->line("You received a new inquiry from {$this->inquiry->name} ({$this->inquiry->email}):")
            ->line("\"{$this->inquiry->message}\"")
            ->action('View Lead in CRM', url('/dashboard/crm'))
            ->line('Respond promptly to increase your sales conversion!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'inquiry_received',
            'inquiry_id' => $this->inquiry->id,
            'listing_id' => $this->inquiry->listing_id,
            'listing_title' => $this->inquiry->listing?->title,
            'buyer_name' => $this->inquiry->name,
            'buyer_email' => $this->inquiry->email,
            'buyer_phone' => $this->inquiry->phone,
            'message' => $this->inquiry->message,
            'created_at' => now()->toIso8601String(),
        ];
    }
}

