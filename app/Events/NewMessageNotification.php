<?php

namespace App\Events;

use App\Models\MessageNotification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageNotification implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int, int>  $recipientIds
     */
    public function __construct(
        public MessageNotification $notification,
        public array $recipientIds,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return collect($this->recipientIds)
            ->map(fn (int $userId) => new PrivateChannel("notifications.{$userId}"))
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'new-message';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'title' => $this->notification->title,
            'message' => $this->notification->message,
            'link' => $this->notification->link,
            'created_at' => $this->notification->created_at,
        ];
    }
}
