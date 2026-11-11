<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    // URL of the flag SVG in public/img/lang, or null when no file exists for the code.
    protected function flagUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $code = strtolower((string) $this->code);

                if ($code === '') {
                    return null;
                }

                $path = 'img/lang/'.$code.'.svg';

                return file_exists(public_path($path)) ? asset($path) : null;
            }
        );
    }
}
