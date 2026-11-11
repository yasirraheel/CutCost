<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'outlet_name',
    'logo_path',
    'logo_text',
    'link_url',
    'sort_order',
    'is_active',
])]
class PressItem extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
