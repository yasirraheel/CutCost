<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

/**
 * Seeds the countries table from the captured static HTML. Nothing here
 * is invented: the country list comes from the hero-search :countries
 * JSON in index.html, and the ISO code plus postal pattern and example
 * for each country come from the my-account country select in
 * my-account.html. Fee and currency are only known for the United
 * Kingdom, from the :current-country object in index.html, so every
 * other country keeps the table defaults (fee 0, currency GBP).
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
                'currency' => 'GBP',
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
