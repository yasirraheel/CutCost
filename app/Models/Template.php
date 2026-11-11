<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'brand_id',
    'name',
    'slug',
    'seats',
    'shareable_seats',
    'available_seats',
    'cost_sharing',
    'credentials_type',
    'frequency',
    'price_with_fees',
    'retail_price',
    'currency',
    'description',
    'description_seo',
    'title_seo',
    'helper_create',
    'helper_join',
    'sort_order',
    'is_active',
])]
class Template extends Model
{
    protected function casts(): array
    {
        return [
            'price_with_fees' => 'decimal:2',
            'retail_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
