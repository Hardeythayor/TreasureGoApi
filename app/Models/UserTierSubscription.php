<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'subscription_tier_id', 'status', 'is_current', 'subscribed_on'])]
class UserTierSubscription extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'subscription_tier_id' => 'integer',
            'subscribed_on' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionTier(): BelongsTo
    {
        return $this->belongsTo(SubscriptionTier::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SubscriptionTierTransaction::class, 'subscription_id');
    }
}
