<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'metric',
    'currency',
    'amount',
    'available_on',
])]
class WalletBalance extends Model
{
    public const METRIC_PENDING = 'pending';

    public const METRIC_AVAILABLE = 'available';

    public const METRIC_TOTAL_EARNINGS = 'total_earnings';

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'available_on' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
