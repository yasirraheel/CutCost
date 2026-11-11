<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'sort_order',
])]
class FooterLinkGroup extends Model
{
    protected function casts(): array
    {
        return [];
    }

    public function footerLinks(): HasMany
    {
        return $this->hasMany(FooterLink::class);
    }
}
