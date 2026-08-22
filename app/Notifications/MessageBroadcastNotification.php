<?php

namespace App\Notifications;

use App\Models\MessageNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MessageBroadcastNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public MessageNotification $notification,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->notification->title)
            ->greeting("Hi {$notifiable->name},")
            ->line($this->notification->message)
            ->action('View', $this->notification->link)
            ->line('Thank you for using '.config('app.name').'!');
    }
}
