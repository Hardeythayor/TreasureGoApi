<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Pusher\PushNotifications\PushNotifications;

class SendPushNotification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, int>  $userIds
     */
    public function __construct(
        public array $userIds,
        public string $title,
        public string $body,
        public ?string $link = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $instanceId = config('services.pusher_beams.instance_id');
        $secretKey = config('services.pusher_beams.secret_key');

        if (! $instanceId || ! $secretKey) {
            return;
        }

        $beams = new PushNotifications([
            'instanceId' => $instanceId,
            'secretKey' => $secretKey,
        ]);

        $beams->publishToUsers(
            array_map(fn ($id) => 'user-'.$id, $this->userIds),
            [
                'web' => [
                    'notification' => [
                        'title' => $this->title,
                        'body' => $this->body,
                        ...($this->link ? ['deep_link' => $this->link] : []),
                    ],
                ],
            ],
        );
    }
}
