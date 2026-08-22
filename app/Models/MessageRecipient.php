<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['message_id', 'receiver_id', 'is_read', 'is_deleted'])]
class MessageRecipient extends Model
{
    public function message(): BelongsTo
    {
        return $this->belongsTo(MessageNotification::class, 'message_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
