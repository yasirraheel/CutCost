<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name',
    'slug',
    'price',
    'currency',
    'currency_symbol',
    'discount_percent',
    'background',
    'icon_svg',
    'logo_path',
    'category_id',
    'short_description',
    'tagline',
    'is_featured',
    'sort_order',
    'is_active',
])]
class Brand extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_percent' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
