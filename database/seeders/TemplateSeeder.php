<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Currency;
use App\Models\Template;
use Illuminate\Database\Seeder;

/**
 * Seeds the templates table from the saving calculator :brands JSON in
 * resources/captured/en/index.html line 2427. Every row is the lowest
 * price template of one of the 13 calculator brands. Nothing here is
 * invented: name, slug, seats, prices and helper texts all come from
 * that JSON.
 *
 * The table is seeded only when it is empty, so running this seeder
 * twice never creates duplicates.
 *
 * Each template is linked to the Brand row with the same slug. Three
 * calculator slugs differ from the Brand slugs and are mapped in
 * SLUG_ALIASES: nordvpn (nord-vpn), office-365 (microsoft) and
 * purevpn (pure-vpn), so all 13 calculator brands seed.
 * brands get a template row.
 *
 * The prices row holds the retail price: prices[0].price is the full
 * retail price (for example 7.99 for Audible) while
 * current_country_price_with_fees is the shared seat price (1.85).
 * The row currency comes from the currencies table by its ISO numeric
 * id; every calculator brand uses the default currency in the capture.
 *
 * Em and en dashes in the captured helper texts (Netflix) are
 * replaced with a plain hyphen and spaces, as elsewhere in the
 * seeders, so no long dash ever lands in the database.
 */
class TemplateSeeder extends Seeder
{
    /** Calculator slugs that differ from the Brand slugs. */
    private const SLUG_ALIASES = [
        'nordvpn' => 'nord-vpn',
        'office-365' => 'microsoft',
        'purevpn' => 'pure-vpn',
    ];

    public function run(): void
    {
        if (Template::query()->exists()) {
            return;
        }

        $brands = $this->calculatorBrands();

        if ($brands === null) {
            return;
        }

        $order = 0;
        foreach ($brands as $entry) {
            $slug = self::SLUG_ALIASES[(string) ($entry['slug'] ?? '')] ?? (string) ($entry['slug'] ?? '');
            $brand = Brand::query()->where('slug', $slug)->first();

            if ($brand === null) {
                continue;
            }

            $template = $entry['lowest_price_template'] ?? null;

            if (! is_array($template)) {
                continue;
            }

            $priceRow = $template['prices'][0] ?? null;

            Template::query()->create([
                'brand_id' => $brand->id,
                'name' => $this->cleanText((string) ($template['name'] ?? '')),
                'slug' => $this->cleanText((string) ($template['slug'] ?? '')),
                'seats' => isset($template['seats']) ? (int) $template['seats'] : null,
                'shareable_seats' => isset($template['shareable_seats']) ? (int) $template['shareable_seats'] : null,
                'available_seats' => isset($template['available_seats']) ? (int) $template['available_seats'] : null,
                'price_with_fees' => (float) ($template['current_country_price_with_fees'] ?? 0),
                'retail_price' => isset($priceRow['price']) && $priceRow['price'] !== null ? (float) $priceRow['price'] : null,
                'currency' => Currency::findByIsoNumeric((int) ($priceRow['currency_id'] ?? 0))?->code ?? Currency::defaultCode(),
                'description' => $this->cleanText((string) ($template['description'] ?? '')) ?: null,
                'description_seo' => $this->cleanText((string) ($template['description_seo'] ?? '')) ?: null,
                'title_seo' => $this->cleanText((string) ($template['title_seo'] ?? '')) ?: null,
                'helper_create' => $this->cleanHtml((string) ($template['helper_create'] ?? '')) ?: null,
                'helper_join' => $this->cleanHtml((string) ($template['helper_join'] ?? '')) ?: null,
                'sort_order' => $order,
                'is_active' => true,
            ]);
            $order++;
        }
    }

    /** @return array<int, mixed>|null */
    private function calculatorBrands(): ?array
    {
        $path = resource_path('captured/en/index.html');

        if (! is_file($path)) {
            return null;
        }

        $html = file_get_contents($path);

        if ($html === false) {
            return null;
        }

        if (! preg_match('/\s:brands=\'(.*?)\'\s*:default-brand=/s', $html, $matches)) {
            return null;
        }

        $decoded = json_decode($matches[1], true);

        return is_array($decoded) ? $decoded : null;
    }

    private function cleanText(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\u{2014}", "\u{2013}"], ' - ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text ?? '');
    }

    private function cleanHtml(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        $text = str_replace(["\u{2014}", "\u{2013}"], ' - ', $text);

        return trim($text);
    }
}
