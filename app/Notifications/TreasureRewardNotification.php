<?php

namespace App\Notifications;

use App\Models\Treasure;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TreasureRewardNotification extends Notification
{
    use Queueable;

    public function __construct(
        public User $user,
        public Treasure $treasure,
        public string $amazonLink,
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
        $amount = $this->treasure->subscriptionTier->reward_amount;

        return (new MailMessage)
            ->subject("Congratulations! You've found the {$this->treasure->name}")
            ->greeting("Hi {$this->user->name},")
            ->line("Congratulations on successfully cracking the clues and locating the {$this->treasure->name}!")
            ->line('Your sharp skills, quick thinking, and determination have officially paid off. You were the first to uncover the hidden treasure and it is an absolute pleasure to award you the grand prize.')
            ->line("As promised, here is your well-deserved treasure: a \${$amount} Amazon Gift Card!")
            ->action('Claim Your Treasure', $this->amazonLink)
            ->line('How to claim your bounty:')
            ->line('Click the link above.')
            ->line("Log in to your Amazon account (if you aren't already).")
            ->line('The gift card balance will automatically be applied to your account for your next purchase.')
            ->line('Thank you so much for participating and making this hunt such a fantastic experience. We hope you enjoy treating yourself to something special with your winnings!')
            ->line('Best adventures,')
            ->line(config('app.name'))
            ->line(config('app.client_url'))
            ->salutation('');
    }
}
