<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'bank_name',
    'masked_reference',
    'currency',
    'stripe_ref',
])]
class BankAccount extends Model
{
    // Only the bank name, a masked reference and the currency are
    // stored. A full IBAN is never stored.

    protected function casts(): array
    {
        return [];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
