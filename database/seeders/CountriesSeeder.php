<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Currency;
use Illuminate\Database\Seeder;

/**
 * Seeds the countries table from the captured static HTML. Nothing here
 * is invented: the country list comes from the hero-search :countries
 * JSON in index.html, and the ISO code plus postal pattern and example
 * for each country come from the my-account country select in
 * my-account.html.
 *
 * Currency comes from the Amazon get-templates price rows in Raw Pages
 * zip (6) (320 rows), keyed by the capture country id: the majority
 * currency per country (237 GB -> GBP; 14 -> AUD; 40 -> CAD; 61 -> CZK;
 * 62 -> DKK; 102 -> HUF; 166 -> NOK; 177 -> PLN; 182 -> RON; 215 -> SEK;
 * 216 -> CHF; every other country with a price row -> EUR). This is
 * Amazon-template evidence only, and only the United Kingdom's currency
 * is directly stated in the capture itself (:current-country, 826 GBP).
 * The 10 countries with no price row (79 French Guiana, 80 French
 * Polynesia, 81 French Southern Territories, 91 Guadeloupe, 138
 * Martinique, 141 Mayotte, 156 New Caledonia, 181 Reunion, 209
 * St. Martin, 245 Wallis & Futuna) keep the default currency: several
 * of those use XPF, which is not among the 21 seeded currencies, so no
 * better value can be derived from the capture.
 *
 * The capture holds 42 countries in :countries, not 44, and the
 * my-account select holds the same 42 countries. The brand filter
 * select in brand/amazon.html holds only 32 of them.
 *
 * The table is seeded only when it is empty, so running this seeder
 * twice never creates duplicates.
 */
class CountriesSeeder extends Seeder
{
    /**
     * Capture country id -> currency code, from the Amazon get-templates
     * price rows (see the class docblock). Countries absent here keep the
     * default currency (see below).
     *
     * @var array<int, string>
     */
    private const CURRENCY_BY_CAPTURE_COUNTRY_ID = [
        14 => 'AUD',
        40 => 'CAD',
        61 => 'CZK',
        62 => 'DKK',
        102 => 'HUF',
        166 => 'NOK',
        177 => 'PLN',
        182 => 'RON',
        215 => 'SEK',
        216 => 'CHF',
        237 => 'GBP',
    ];

    /** @var array<int, string> */
    private const EUR_COUNTRY_IDS = [
        15, 22, 35, 57, 60, 71, 77, 78, 85, 88, 108, 111, 122, 128, 129,
        136, 155, 178, 195, 196, 203,
    ];

    public function run(): void
    {
        if (Country::query()->exists()) {
            return;
        }

        $indexHtml = $this->rawHtml(resource_path('captured/en/index.html'));
        $accountHtml = $this->rawHtml(resource_path('captured/en/my-account.html'));

        if ($indexHtml === null || ! preg_match("/\:countries='(\[.*?\])'/s", $indexHtml, $matches)) {
            return;
        }

        $countries = json_decode($matches[1], true);
        if (! is_array($countries)) {
            return;
        }

        $account = [];
        if ($accountHtml !== null) {
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML(mb_convert_encoding($accountHtml, 'HTML-ENTITIES', 'UTF-8'));
            libxml_clear_errors();
            $xpath = new \DOMXPath($dom);
            $options = $xpath->query('//select[@name="country"]//option[@value != "#offline"]');
            if ($options !== false) {
                foreach ($options as $option) {
                    $name = trim(preg_replace('/\s+/u', ' ', html_entity_decode($option->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
                    if ($name === '') {
                        continue;
                    }
                    $pattern = trim($option->getAttribute('data-postal-pattern'));
                    $example = trim($option->getAttribute('data-postal-example'));
                    $account[$name] = [
                        'code' => strtoupper(trim($option->getAttribute('value'))),
                        'postal_regex' => $pattern !== '' ? $pattern : null,
                        'postal_example' => $example !== '' ? $example : null,
                    ];
                }
            }
        }

        foreach ($countries as $order => $country) {
            $name = $country['name'] ?? '';
            if ($name === '') {
                continue;
            }

            $details = $account[$name] ?? null;
            $code = $details['code'] ?? null;
            if ($code === null || mb_strlen($code) !== 2) {
                continue;
            }

            Country::query()->create([
                'code' => $code,
                'name' => $name,
                'fee_flat' => $code === 'GB' ? '1.000' : '0.000',
                'fee_percent' => $code === 'GB' ? '0.080' : '0.000',
                'currency' => self::CURRENCY_BY_CAPTURE_COUNTRY_ID[(int) ($country['id'] ?? 0)]
                    ?? (in_array((int) ($country['id'] ?? 0), self::EUR_COUNTRY_IDS, true) ? 'EUR' : Currency::defaultCode()),
                'postal_regex' => $details['postal_regex'] ?? null,
                'postal_example' => $details['postal_example'] ?? null,
                'sort_order' => $order,
                'is_active' => true,
            ]);
        }
    }

    private function rawHtml(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $html = file_get_contents($path);

        return $html === false ? null : $html;
    }
}
