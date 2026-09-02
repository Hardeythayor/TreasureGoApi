<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sender_id', 'type', 'message_type', 'subscription_tier_id', 'title', 'message', 'recipients', 'recipient_count', 'link'])]
class MessageNotification extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sender_id' => 'integer',
            'subscription_tier_id' => 'integer',
            'recipients' => 'array',
            'recipient_count' => 'integer',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function subscriptionTier(): BelongsTo
    {
        return $this->belongsTo(SubscriptionTier::class);
    }
}
