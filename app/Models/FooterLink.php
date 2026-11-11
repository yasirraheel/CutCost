<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'footer_link_group_id',
    'label',
    'url',
    'sort_order',
    'is_active',
])]
class FooterLink extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function footerLinkGroup(): BelongsTo
    {
        return $this->belongsTo(FooterLinkGroup::class);
    }
}
