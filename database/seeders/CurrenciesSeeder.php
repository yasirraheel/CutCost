<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

/**
 * Seeds the currencies table with EXACTLY the 21 rows of the captured
 * wizard :currencies prop (create-subscription.html in Raw Pages zip (6),
 * in prop order, ascending ISO numeric id). Copied verbatim: id is the
 * ISO 4217 numeric code, symbol is copied as served (CHF's symbol is the
 * code itself, MKD's "den" is Cyrillic). Nothing invented.
 *
 * USD (840) is NOT in the capture (the string USD occurs 0 times and the
 * 21-row prop has no 840 row), but the user asked on 2026-11-11 to add it
 * anyway, so it is seeded as a 22nd row, appended after PLN. It is always
 * offered in the wizard, which restores the capture's app.js base list
 * [978, 840] (EUR + USD) exactly: the 840 filter only dropped USD because
 * the row was missing from the prop.
 *
 * Wizard flags: every seeded row is available in the wizard
 * (is_available_in_wizard true); EUR and USD are always offered
 * (always_offered_in_wizard true), mirroring the capture's base list
 * [978, 840]. The country currency is offered alongside EUR and USD when
 * the country row names a code that exists here and is available; a
 * country whose currency is not among the seeded rows (e.g. XPF for
 * French Polynesia) is offered EUR and USD only.
 *
 * The table is seeded only when it is empty, so running this seeder
 * twice never creates duplicates.
 */
class CurrenciesSeeder extends Seeder
{
    /** @var array<int, array{code: string, symbol: string, name: string, iso_numeric: int}> */
    private const ROWS = [
        ['code' => 'ALL', 'symbol' => 'L', 'name' => 'Lek', 'iso_numeric' => 8],
        ['code' => 'AUD', 'symbol' => '$', 'name' => 'Australian Dollar', 'iso_numeric' => 36],
        ['code' => 'CAD', 'symbol' => '$', 'name' => 'Canadian Dollar', 'iso_numeric' => 124],
        ['code' => 'CZK', 'symbol' => 'Kč', 'name' => 'Czech Koruna', 'iso_numeric' => 203],
        ['code' => 'DKK', 'symbol' => 'kr', 'name' => 'Danish Krone', 'iso_numeric' => 208],
        ['code' => 'GIP', 'symbol' => '£', 'name' => 'Gibraltar Pound', 'iso_numeric' => 292],
        ['code' => 'HUF', 'symbol' => 'Ft', 'name' => 'Forint', 'iso_numeric' => 348],
        ['code' => 'ISK', 'symbol' => 'kr', 'name' => 'Iceland Krona', 'iso_numeric' => 352],
        ['code' => 'MDL', 'symbol' => 'lei', 'name' => 'Moldovan Leu', 'iso_numeric' => 498],
        ['code' => 'NOK', 'symbol' => 'kr', 'name' => 'Norwegian Krone', 'iso_numeric' => 578],
        ['code' => 'RUB', 'symbol' => '₽', 'name' => 'Russian Ruble', 'iso_numeric' => 643],
        ['code' => 'SEK', 'symbol' => 'kr', 'name' => 'Swedish Krona', 'iso_numeric' => 752],
        ['code' => 'CHF', 'symbol' => 'CHF', 'name' => 'Swiss Franc', 'iso_numeric' => 756],
        ['code' => 'MKD', 'symbol' => 'ден', 'name' => 'Denar', 'iso_numeric' => 807],
        ['code' => 'GBP', 'symbol' => '£', 'name' => 'Pound Sterling', 'iso_numeric' => 826],
        ['code' => 'BYN', 'symbol' => 'Br', 'name' => 'Belarussian Ruble', 'iso_numeric' => 933],
        ['code' => 'RON', 'symbol' => 'lei', 'name' => 'Romanian Leu', 'iso_numeric' => 946],
        ['code' => 'BAM', 'symbol' => 'KM', 'name' => 'Convertible Mark', 'iso_numeric' => 977],
        ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro', 'iso_numeric' => 978],
        ['code' => 'UAH', 'symbol' => '₴', 'name' => 'Hryvnia', 'iso_numeric' => 980],
        ['code' => 'PLN', 'symbol' => 'zł', 'name' => 'Zloty', 'iso_numeric' => 985],
        // Not in the capture: added 2026-11-11 at the user's request.
        ['code' => 'USD', 'symbol' => '$', 'name' => 'US Dollar', 'iso_numeric' => 840],
    ];

    public function run(): void
    {
        if (Currency::query()->exists()) {
            return;
        }

        foreach (self::ROWS as $order => $row) {
            Currency::query()->create(array_merge($row, [
                'is_active' => true,
                'is_available_in_wizard' => true,
                'always_offered_in_wizard' => in_array($row['code'], ['EUR', 'USD'], true),
                'sort_order' => $order,
            ]));
        }
    }
}
