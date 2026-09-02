<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['subscription_id', 'transaction_reference', 'payment_type', 'amount', 'status', 'raw_response'])]
class SubscriptionTierTransaction extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subscription_id' => 'integer',
            'raw_response' => 'array',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserTierSubscription::class, 'subscription_id');
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'TXN-'.strtoupper(Str::random(16));
        } while (static::where('transaction_reference', $reference)->exists());

        return $reference;
    }
}
