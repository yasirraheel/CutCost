<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Offer;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Seeder;

/**
 * Seeds the offers table by parsing the join offer rows in the captured
 * Amazon brand page in resources/captured/en/brand/amazon.html. Nothing
 * here is invented: every price, plan title, seat count and seller name
 * comes from that capture.
 *
 * The capture lists only seats remaining, never a total seat count, so
 * seats_total stays null. The capture has no per offer currency, so every
 * offer is GBP like the brand itself.
 *
 * The table is seeded only when it is empty, so running this seeder twice
 * never creates duplicates. The Amazon tagline is set from the page meta
 * description when it is still empty, reads cleanly and has no em or en
 * dash; other brands keep an empty tagline.
 */
class OfferSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAmazonTagline();

        if (Offer::query()->exists()) {
            return;
        }

        $brand = Brand::query()->where('slug', 'amazon')->first();
        if ($brand === null) {
            return;
        }

        $dom = $this->loadDocument(resource_path('captured/en/brand/amazon.html'));
        if ($dom === null) {
            return;
        }

        $xpath = new DOMXPath($dom);
        $rows = $xpath->query('//div[@id="subscriptions-list"]//div['.$this->token('join-content').']');
        if ($rows === false) {
            return;
        }

        $order = 0;
        foreach ($rows as $row) {
            $sellerNode = $xpath->query('.//span['.$this->token('join-content-name').']', $row)->item(0);
            $titleNode = $xpath->query('.//div['.$this->token('join-content-title').']//strong', $row)->item(0);
            $priceNode = $xpath->query('.//span['.$this->token('join-action-header-price').']', $row)->item(0);

            $title = $titleNode !== null ? $this->clean($titleNode->textContent) : '';
            $priceText = $priceNode !== null ? preg_replace('/[^0-9.]/', '', $priceNode->textContent) : '';
            if ($title === '' || $priceText === '' || $priceText === null) {
                continue;
            }

            $seller = $sellerNode !== null ? $this->clean($sellerNode->textContent) : '';
            $seatsAvailable = null;
            $spans = $xpath->query('.//span', $row);
            if ($spans !== false) {
                foreach ($spans as $span) {
                    if (preg_match('/(\d+)\s+seats?\s+remaining/i', $span->textContent, $matches)) {
                        $seatsAvailable = (int) $matches[1];
                        break;
                    }
                }
            }

            Offer::query()->create([
                'brand_id' => $brand->id,
                'title' => $title,
                'price' => (float) $priceText,
                'currency' => 'GBP',
                'seats_total' => null,
                'seats_available' => $seatsAvailable,
                'seller_name' => $seller !== '' ? $seller : null,
                'sort_order' => $order,
                'is_active' => true,
            ]);
            $order++;
        }
    }

    private function seedAmazonTagline(): void
    {
        $brand = Brand::query()->where('slug', 'amazon')->whereNull('tagline')->first();
        if ($brand === null) {
            return;
        }

        $dom = $this->loadDocument(resource_path('captured/en/brand/amazon.html'));
        if ($dom === null) {
            return;
        }

        $xpath = new DOMXPath($dom);
        $meta = $xpath->query('//meta[@name="description"]')->item(0);
        if ($meta === null) {
            return;
        }

        $raw = $meta->getAttribute('content');
        if (str_contains($raw, "\u{2014}") || str_contains($raw, "\u{2013}")) {
            return;
        }

        $tagline = $this->clean($raw);
        if ($tagline === '' || mb_strlen($tagline) > 255) {
            return;
        }

        $brand->update(['tagline' => $tagline]);
    }

    private function loadDocument(string $path): ?DOMDocument
    {
        if (! is_file($path)) {
            return null;
        }

        $html = file_get_contents($path);
        if ($html === false) {
            return null;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        return $dom;
    }

    private function token(string $class): string
    {
        return 'contains(concat(" ", normalize-space(@class), " "), " '.$class.' ")';
    }

    private function clean(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text ?? '');
    }
}
