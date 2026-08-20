<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['subscription_id', 'transaction_reference', 'payment_type', 'amount', 'status', 'raw_response'])]
class SubscriptionTierTransaction extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raw_response' => 'array',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserTierSubscription::class, 'subscription_id');
    }
}
