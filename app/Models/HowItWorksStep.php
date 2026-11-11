<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title',
    'text',
    'icon_svg',
    'audience',
    'mockup_image_path',
    'sort_order',
    'is_active',
])]
class HowItWorksStep extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
