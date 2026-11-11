<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'offer_id',
    'user_id',
    'username',
    'initials',
    'joined_at',
    'left_at',
    'status',
    'invitation_email',
    'invite_sent_at',
])]
class OfferMember extends Model
{
    public const STATUS_CURRENT = 'current';

    public const STATUS_PREVIOUS = 'previous';

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
            'invite_sent_at' => 'datetime',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'member_id');
    }

    public function isCurrent(): bool
    {
        return $this->status === self::STATUS_CURRENT;
    }

    public function isPrevious(): bool
    {
        return $this->status === self::STATUS_PREVIOUS;
    }
}
