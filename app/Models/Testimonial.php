<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'author_name',
    'role_text',
    'quote',
    'rating',
    'avatar_path',
    'sort_order',
    'is_active',
])]
class Testimonial extends Model
{
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
