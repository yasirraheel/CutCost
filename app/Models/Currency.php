<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

#[Fillable([
    'code',
    'symbol',
    'name',
    'iso_numeric',
    'is_active',
    'is_available_in_wizard',
    'always_offered_in_wizard',
    'sort_order',
])]
class Currency extends Model
{
    protected function casts(): array
    {
        return [
            'iso_numeric' => 'integer',
            'is_active' => 'boolean',
            'is_available_in_wizard' => 'boolean',
            'always_offered_in_wizard' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeAvailableInWizard($query)
    {
        return $query->where('is_available_in_wizard', true);
    }

    public function scopeAlwaysOffered($query)
    {
        return $query->where('always_offered_in_wizard', true);
    }

    /** Find a currency row by its 3-letter code, or null. Never throws. */
    public static function findByCode(?string $code): ?self
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '') {
            return null;
        }

        try {
            if (! Schema::hasTable('currencies')) {
                return null;
            }

            return static::query()->where('code', $code)->first();
        } catch (Throwable) {
            return null;
        }
    }

    /** Find a currency row by its ISO 4217 numeric code, or null. Never throws. */
    public static function findByIsoNumeric(?int $isoNumeric): ?self
    {
        if ($isoNumeric === null) {
            return null;
        }

        try {
            if (! Schema::hasTable('currencies')) {
                return null;
            }

            return static::query()->where('iso_numeric', $isoNumeric)->first();
        } catch (Throwable) {
            return null;
        }
    }

    /** The symbol for a code (e.g. GBP -> £), or null when the row is missing. */
    public static function symbolFor(?string $code): ?string
    {
        $symbol = static::findByCode($code)?->symbol;

        return $symbol === null || $symbol === '' ? null : (string) $symbol;
    }

    /** The ISO 4217 numeric code for a code (e.g. GBP -> 826), or null. */
    public static function isoNumericForCode(?string $code): ?int
    {
        $iso = static::findByCode($code)?->iso_numeric;

        return $iso === null ? null : (int) $iso;
    }

    /**
     * Default currency code from the default_currency_code site setting.
     * GBP is the fallback: the capture's current country is the United
     * Kingdom (GB, currency 826 GBP), and the setting is seeded GBP.
     */
    public static function defaultCode(): string
    {
        try {
            if (Schema::hasTable('site_settings')) {
                $code = SiteSetting::where('key', 'default_currency_code')->value('value');
                if (is_string($code)) {
                    $code = strtoupper(trim($code));
                    if (mb_strlen($code) === 3) {
                        return $code;
                    }
                }
            }
        } catch (Throwable) {
            // Fall through to the seeded default.
        }

        return 'GBP';
    }

    /** The default currency row, or null when the table is not seeded yet. */
    public static function defaultCurrency(): ?self
    {
        return static::findByCode(static::defaultCode());
    }

    /** The default currency symbol, or null when the row is missing. */
    public static function defaultSymbol(): ?string
    {
        return static::symbolFor(static::defaultCode());
    }

    /**
     * Resolve a currency code to its ISO numeric id for payloads, falling
     * back to the default currency row when the code has no row, and to
     * null when even the default row is missing (unseeding DB). Callers
     * must not invent a numeric id here.
     */
    public static function isoNumericForPayload(?string $code): ?int
    {
        return static::isoNumericForCode($code)
            ?? static::defaultCurrency()?->iso_numeric;
    }

    /** Rows for a wizard currency dropdown: code => symbol, ordered. */
    public static function optionsForSelect(): array
    {
        try {
            if (! Schema::hasTable('currencies')) {
                return [];
            }

            return static::query()->active()->ordered()
                ->pluck('symbol', 'code')->all();
        } catch (Throwable) {
            return [];
        }
    }
}
