<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'brand',
    'last4',
    'exp_month',
    'exp_year',
    'holder_name',
    'is_main',
    'stripe_ref',
])]
class PaymentMethod extends Model
{
    // Only the brand, last four digits, expiry and holder name are
    // stored. A full card number is never stored.

    protected function casts(): array
    {
        return [
            'exp_month' => 'integer',
            'exp_year' => 'integer',
            'is_main' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
