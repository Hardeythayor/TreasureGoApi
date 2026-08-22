<?php

namespace App\Services;

use App\Events\NewMessageNotification;
use App\Models\MessageNotification;
use App\Models\MessageRecipient;
use App\Models\User;
use App\Notifications\MessageBroadcastNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class MessageBroadcastService
{
    /**
     * Persist a notification for the given recipients, broadcast it in real
     * time (chunked to stay under Pusher's 100-channel-per-call limit), and
     * queue an email to each recipient.
     *
     * Returns null (no-op) if there are no recipients to notify.
     */
    public function send(
        string $type,
        string $title,
        string $message,
        string $link,
        Collection $recipientIds,
        ?int $senderId = null,
    ): ?MessageNotification {
        if ($recipientIds->isEmpty()) {
            return null;
        }

        $notification = DB::transaction(function () use ($type, $title, $message, $link, $recipientIds, $senderId) {
            $notification = MessageNotification::create([
                'sender_id' => $senderId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'recipients' => $recipientIds->values()->all(),
                'recipient_count' => $recipientIds->count(),
                'link' => $link,
            ]);

            $rows = $recipientIds->map(fn ($id) => [
                'message_id' => $notification->id,
                'receiver_id' => $id,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            MessageRecipient::insert($rows);

            return $notification;
        });

        foreach ($recipientIds->values()->chunk(100) as $chunk) {
            try {
                broadcast(new NewMessageNotification($notification, $chunk->all()));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        User::whereIn('id', $recipientIds->values()->all())->chunk(200, function ($users) use ($notification) {
            try {
                Notification::send($users, new MessageBroadcastNotification($notification));
            } catch (\Throwable $e) {
                report($e);
            }
        });

        return $notification;
    }
}
