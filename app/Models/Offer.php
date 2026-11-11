<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'brand_id',
    'user_id',
    'status',
    'hash',
    'template_id',
    'category_id',
    'country_id',
    'title',
    'name',
    'description',
    'share',
    'friends',
    'total_seats',
    'total_price',
    'custom_price',
    'frequency',
    'visibility',
    'lat',
    'lng',
    'email',
    'secured_description',
    'credentials',
    'price',
    'currency',
    'seats_total',
    'seats_available',
    'seller_name',
    'sort_order',
    'is_active',
])]
class Offer extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'is_active' => 'boolean',
            'custom_price' => 'boolean',
            'lat' => 'float',
            'lng' => 'float',
            'secured_description' => 'array',
            // Credential values are encrypted at rest, never stored plain.
            'credentials' => 'encrypted:array',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(OfferMember::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * Offer review states. The original Sharesub captures show no
     * approval step for a shared subscription (review wording in the
     * captures appears only for identity documents), so the review
     * workflow follows the user's own words and the app's existing
     * KYC approve/reject pattern instead. A wizard publish lands in
     * pending review; an admin approves it to active or rejects it.
     * 'pending' is the value the codebase already uses for that state
     * (OfferPayment::STATUS_PENDING). The column is a plain string,
     * so no migration is needed.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_REVIEW = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_TERMINATED = 'terminated';

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPendingReview(): bool
    {
        return $this->status === self::STATUS_PENDING_REVIEW;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_REVIEW => 'Pending review',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_TERMINATED => 'Terminated',
            default => 'Active',
        };
    }
}
