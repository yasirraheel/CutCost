<?php

namespace App\Services;

/**
 * Single place that answers "is Stripe really configured?".
 *
 * A key counts as real only when it is non-empty and does NOT contain
 * the string PLACEHOLDER. Placeholder values in .env / .env.example
 * are fake and never count as configured.
 *
 * Only the publishable key may be exposed to page JS, via
 * publishableKey(). The secret and webhook secret must never be
 * exposed anywhere.
 */
class StripeConfig
{
    public static function isConfigured(): bool
    {
        return self::isRealKey(config('services.stripe.key'))
            && self::isRealKey(config('services.stripe.secret'));
    }

    public static function isRealKey(?string $key): bool
    {
        return $key !== null && $key !== '' && ! str_contains($key, 'PLACEHOLDER');
    }

    public static function publishableKey(): ?string
    {
        return self::isConfigured() ? config('services.stripe.key') : null;
    }
}
