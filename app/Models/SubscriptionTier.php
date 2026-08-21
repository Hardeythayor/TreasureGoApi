<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'amount', 'reward_amount', 'validity', 'type', 'status'])]
class SubscriptionTier extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'validity' => 'integer',
        ];
    }

    public function currentUserSubscription(): HasOne
    {
        return $this->hasOne(UserTierSubscription::class);
    }
}
