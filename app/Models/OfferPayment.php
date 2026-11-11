<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'offer_id',
    'member_id',
    'month',
    'status',
    'paid_at',
    'amount',
    'currency',
])]
class OfferPayment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_LATE = 'late';

    protected function casts(): array
    {
        return [
            'month' => 'date',
            'paid_at' => 'date',
            'amount' => 'decimal:2',
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

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }
}
