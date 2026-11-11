<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'offer_id',
    'member_id',
    'sender_id',
    'body',
    'is_new',
    'is_read',
    'is_notified',
    'is_moderated',
    'waiting_moderation',
    'admin_override',
])]
class ChatMessage extends Model
{
    // The body column stores PLAINTEXT in v1. The latest-messages API
    // shape names the field `crypted`, but v1 stores plaintext and the
    // endpoint (built in Phase 2) will return the body there. Real
    // encryption is an open follow-up needing a key decision.

    protected function casts(): array
    {
        return [
            'is_new' => 'boolean',
            'is_read' => 'boolean',
            'is_notified' => 'boolean',
            'is_moderated' => 'boolean',
            'waiting_moderation' => 'integer',
            'admin_override' => 'boolean',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(OfferMember::class, 'member_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
