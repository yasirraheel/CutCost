<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The currencies table was seeded with 21 rows before USD was added
     * to the seeder (user request 2026-11-11), and the countries table
     * was seeded with every currency as GBP before the per-country
     * mapping landed; both seeders only fill empty tables, so neither
     * fix reaches an existing database by re-seeding. This migration
     * applies both to every database: it inserts the USD row if missing
     * and backfills each country's currency from the captured
     * get-templates price rows (the same mapping CountriesSeeder uses
     * for a fresh seed: the 11 explicit capture ids, EUR for the other
     * 21 countries with a price row, default GBP for the 10 without).
     * The USD insert is skipped on an empty table so a fresh database
     * still gets all 22 rows from the seeder instead of just USD.
     */
    public function up(): void
    {
        // Insert USD only into an already-seeded table. On a fresh
        // database the table is empty and the seeder fills all 22 rows;
        // inserting here would make the seeder's empty-table guard skip
        // entirely and leave USD as the only row.
        if (Schema::hasTable('currencies')
            && DB::table('currencies')->count() > 0
            && DB::table('currencies')->where('code', 'USD')->doesntExist()
        ) {
            DB::table('currencies')->insert([
                'code' => 'USD',
                'symbol' => '$',
                'name' => 'US Dollar',
                'iso_numeric' => 840,
                'is_active' => true,
                'is_available_in_wizard' => true,
                'always_offered_in_wizard' => true,
                'sort_order' => 21,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('countries') || ! Schema::hasTable('currencies')) {
            return;
        }

        $currencies = DB::table('currencies')->pluck('code')->all();

        foreach (self::CURRENCY_BY_COUNTRY_CODE as $code => $currency) {
            if (! in_array($currency, $currencies, true)) {
                continue;
            }

            DB::table('countries')->where('code', $code)->update(['currency' => $currency]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('countries')) {
            foreach (self::CURRENCY_BY_COUNTRY_CODE as $code => $currency) {
                DB::table('countries')
                    ->where('code', $code)
                    ->where('currency', $currency)
                    ->update(['currency' => 'GBP']);
            }
        }

        if (Schema::hasTable('currencies')) {
            DB::table('currencies')->where('code', 'USD')->delete();
        }
    }

    /**
     * ISO country code => currency code, from the captured Amazon
     * get-templates price rows. GB is not listed: it keeps GBP.
     *
     * @var array<string, string>
     */
    private const CURRENCY_BY_COUNTRY_CODE = [
        'AT' => 'EUR',
        'AU' => 'AUD',
        'BE' => 'EUR',
        'BG' => 'EUR',
        'CA' => 'CAD',
        'CH' => 'CHF',
        'CY' => 'EUR',
        'CZ' => 'CZK',
        'DE' => 'EUR',
        'DK' => 'DKK',
        'EE' => 'EUR',
        'ES' => 'EUR',
        'FI' => 'EUR',
        'FR' => 'EUR',
        'GR' => 'EUR',
        'HR' => 'EUR',
        'HU' => 'HUF',
        'IE' => 'EUR',
        'IT' => 'EUR',
        'LT' => 'EUR',
        'LU' => 'EUR',
        'LV' => 'EUR',
        'MT' => 'EUR',
        'NL' => 'EUR',
        'NO' => 'NOK',
        'PL' => 'PLN',
        'PT' => 'EUR',
        'RO' => 'RON',
        'SE' => 'SEK',
        'SI' => 'EUR',
        'SK' => 'EUR',
    ];
};
