<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code',
    'name',
    'fee_flat',
    'fee_percent',
    'currency',
    'postal_regex',
    'postal_example',
    'sort_order',
    'is_active',
])]
class Country extends Model
{
    protected function casts(): array
    {
        return [
            'fee_flat' => 'decimal:3',
            'fee_percent' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }
}
