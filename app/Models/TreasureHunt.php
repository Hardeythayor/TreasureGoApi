<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'treasure_id', 'status', 'found_at', 'reward_status', 'reward', 'rewarded_at'])]
class TreasureHunt extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'treasure_id' => 'integer',
            'found_at' => 'datetime',
            'rewarded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function treasure(): BelongsTo
    {
        return $this->belongsTo(Treasure::class);
    }
}
