<?php

namespace App\Console\Commands;

use App\Models\UserTierSubscription;
use App\Services\MessageBroadcastService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('subscriptions:notify-expiring')]
#[Description('Notify users whose active subscription renews/expires in 5 days')]
class NotifyExpiringSubscriptions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(MessageBroadcastService $broadcaster): int
    {
        $subscriptions = UserTierSubscription::query()
            ->join('subscription_tiers', 'subscription_tiers.id', '=', 'user_tier_subscriptions.subscription_tier_id')
            ->where('user_tier_subscriptions.status', 'active')
            ->whereNotNull('user_tier_subscriptions.subscribed_on')
            ->whereRaw(
                'DATE(DATE_ADD(user_tier_subscriptions.subscribed_on, INTERVAL subscription_tiers.validity DAY)) = ?',
                [now()->addDays(5)->toDateString()]
            )
            ->select('user_tier_subscriptions.*')
            ->with('subscriptionTier')
            ->get();

        foreach ($subscriptions as $subscription) {
            $broadcaster->send(
                type: 'user',
                messageType: 'subscription_expiring',
                title: 'Subscription reminder',
                message: "Your {$subscription->subscriptionTier->name} Treasure Pass renews in 5 days.",
                link: config('app.client_url').'/subscriptions',
                recipientIds: collect([$subscription->user_id]),
            );
        }

        $this->info("Notified {$subscriptions->count()} user(s) of an expiring subscription.");

        return self::SUCCESS;
    }
}
