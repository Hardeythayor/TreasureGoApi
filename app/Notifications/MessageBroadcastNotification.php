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
        $message = $this->notification->message;

        // The message may be plain text or HTML from a rich text editor (e.g.
        // CKEditor). Laravel's default markdown mail template escapes all
        // content, which would render CKEditor's HTML as literal tags rather
        // than formatting — so plain text is escaped and line-break-safe here,
        // while HTML is passed through untouched to a raw Blade view instead.
        $isHtml = strip_tags($message) !== $message;
        $body = $isHtml ? $message : nl2br(e($message));

        return (new MailMessage)
            ->subject($this->notification->title)
            ->view('emails.message-broadcast', [
                'title' => $this->notification->title,
                'greeting' => "Hi {$notifiable->name},",
                'body' => $body,
                'link' => $this->notification->link,
            ]);
    }
}
