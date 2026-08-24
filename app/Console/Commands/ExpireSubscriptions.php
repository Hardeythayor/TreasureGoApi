<?php

namespace App\Console\Commands;

use App\Models\UserTierSubscription;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('subscriptions:expire')]
#[Description('Mark active user subscriptions as expired once their validity period has elapsed')]
class ExpireSubscriptions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredIds = UserTierSubscription::query()
            ->join('subscription_tiers', 'subscription_tiers.id', '=', 'user_tier_subscriptions.subscription_tier_id')
            ->where('user_tier_subscriptions.status', 'active')
            ->whereNotNull('user_tier_subscriptions.subscribed_on')
            ->whereRaw('DATE_ADD(user_tier_subscriptions.subscribed_on, INTERVAL subscription_tiers.validity DAY) <= ?', [now()])
            ->pluck('user_tier_subscriptions.id');

        $count = UserTierSubscription::whereIn('id', $expiredIds)->update(['status' => 'expired']);

        $this->info("Expired {$count} subscription(s).");

        return self::SUCCESS;
    }
}
