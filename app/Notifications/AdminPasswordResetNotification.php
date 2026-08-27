<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminPasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $newPassword,
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
            ->subject('Your password has been reset')
            ->greeting("Hi {$notifiable->name},")
            ->line('An administrator has reset your account password.')
            ->line("Your new password is: **{$this->newPassword}**")
            ->line('Please log in and change this password as soon as possible.')
            ->line('If you did not expect this, please contact support immediately.');
    }
}
