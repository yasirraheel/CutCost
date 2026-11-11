<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Feature;
use App\Models\FooterLink;
use App\Models\FooterLinkGroup;
use App\Models\HowItWorksStep;
use App\Models\PressItem;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the admin content tables by parsing the captured static HTML
 * in resources/captured/en at seed time. Nothing here is invented: every
 * value comes from index.html, subscriptions.html or brand/amazon.html.
 *
 * Every section is guarded: a table is seeded only when it is empty, so
 * running this seeder twice never creates duplicates.
 *
 * Notes on what is deliberately not seeded:
 * - Brand short_description is filled only for Amazon, from the first
 *   sentence of its detail page lead. The full lead is 645 chars and does
 *   not fit the 255 char column, and no other brand has a short text.
 * - Brand tagline is left null for all brands: no clean tagline exists in
 *   the captured pages.
 * - How-it-works seeds only the 4 "I want to share" steps. The capture
 *   also has 4 "I want a seat" steps, but the table has no column to tell
 *   the two sets apart, and the spec calls for 4 steps.
 * - Press link_url is null: every press link in the capture is href="#".
 * - The hero search is a Vue component with no placeholder attribute in
 *   the capture, so hero_search_placeholder comes from the only search
 *   placeholder text in the file, "Search for a subscription...".
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $index = $this->loadDocument(resource_path('captured/en/index.html'));
        $subscriptions = $this->loadDocument(resource_path('captured/en/subscriptions.html'));
        $amazon = $this->loadDocument(resource_path('captured/en/brand/amazon.html'));

        if ($index === null) {
            return;
        }

        $this->seedCategories($index);
        $this->seedBrands($index, $subscriptions, $amazon);
        $this->seedSettings($index);
        $this->seedHowItWorksSteps($index);
        $this->seedFaqs($index);
        $this->seedTestimonials($index);
        $this->seedPressItems($index);
        $this->seedFooter($index);
        $this->seedFeatures($index);
    }

    private function seedCategories(DOMDocument $index): void
    {
        if (Category::query()->exists()) {
            return;
        }

        $xpath = new DOMXPath($index);
        $pills = $xpath->query('//div[@id="hero-categories-slider"]//span['.$this->token('hero-categories-item').']');

        if ($pills === false) {
            return;
        }

        $order = 0;
        foreach ($pills as $pill) {
            $link = $xpath->query('.//a', $pill)->item(0);
            $name = $link !== null ? $this->clean($link->textContent) : '';
            if ($name === '') {
                continue;
            }

            $slug = $this->categorySlug($name, $link !== null ? $link->getAttribute('href') : '');
            $svg = $xpath->query('.//svg', $pill)->item(0);

            Category::query()->create([
                'name' => $name,
                'slug' => $slug,
                'icon_svg' => $svg !== null ? $this->outerHtml($svg) : null,
                'sort_order' => $order,
                'is_active' => true,
            ]);
            $order++;
        }
    }

    private function seedBrands(DOMDocument $index, ?DOMDocument $subscriptions, ?DOMDocument $amazon): void
    {
        if (Brand::query()->exists()) {
            return;
        }

        $categoryMap = $this->brandCategoryMap($subscriptions);
        $amazonDescription = $this->amazonShortDescription($amazon);

        $xpath = new DOMXPath($index);
        $slides = $xpath->query('//div[@id="hero-card-slider"]//div['.$this->token('hero-slides-item').']');

        if ($slides === false) {
            return;
        }

        $order = 0;
        foreach ($slides as $slide) {
            $title = $xpath->query('.//a['.$this->token('hero-slides-item-title').']', $slide)->item(0);
            $priceNode = $xpath->query('.//div['.$this->token('hero-slides-item-price').']', $slide)->item(0);
            $participation = $xpath->query('.//div['.$this->token('hero-slides-item-participation').']', $slide)->item(0);

            $name = $title !== null ? $this->clean($title->textContent) : '';
            if ($name === '' || $priceNode === null) {
                continue;
            }

            $price = (float) preg_replace('/[^0-9.]/', '', $priceNode->textContent);
            $discount = null;
            if ($participation !== null && preg_match('/-(\d+)\s*%/', $participation->textContent, $matches)) {
                $discount = (int) $matches[1];
            }

            $background = null;
            if (preg_match('/background:\s*(.+?)\s*;?\s*$/', $slide->getAttribute('style'), $matches)) {
                $background = trim($matches[1]);
            }

            $icon = $xpath->query('.//div['.$this->token('hero-slides-item-icon').']//svg', $slide)->item(0);
            if ($icon === null) {
                $icon = $xpath->query('.//svg', $slide)->item(0);
            }

            $categoryId = null;
            $categoryName = $categoryMap[mb_strtolower($name)] ?? null;
            if ($categoryName !== null) {
                $categoryId = Category::query()->where('slug', Str::slug($categoryName))->value('id');
            }

            Brand::query()->create([
                'name' => $name,
                'slug' => Str::slug($name),
                'price' => $price,
                'currency' => 'GBP',
                'currency_symbol' => '£',
                'discount_percent' => $discount,
                'background' => $background,
                'icon_svg' => $icon !== null ? $this->outerHtml($icon) : null,
                'logo_path' => null,
                'category_id' => $categoryId,
                'short_description' => $name === 'Amazon' ? $amazonDescription : null,
                'tagline' => null,
                'is_featured' => true,
                'sort_order' => $order,
                'is_active' => true,
            ]);
            $order++;
        }
    }

    private function seedSettings(DOMDocument $index): void
    {
        if (SiteSetting::query()->exists()) {
            return;
        }

        $xpath = new DOMXPath($index);
        $values = [];

        $h1 = $xpath->query('//h1['.$this->token('hero-title').']')->item(0);
        if ($h1 !== null) {
            $middle = '';
            foreach ($h1->childNodes as $child) {
                if ($child->nodeName === 'span') {
                    $values['hero_highlight'] = $this->clean($child->textContent);
                } elseif ($child->nodeName === 'u') {
                    $values['hero_underline'] = $this->clean($child->textContent);
                } elseif ($child->nodeType === XML_TEXT_NODE) {
                    $middle .= ' '.$child->textContent;
                }
            }
            $values['hero_text'] = $this->clean($middle);
        }

        $placeholder = $xpath->query('//span['.$this->token('mockup-search-placeholder').']')->item(0);
        if ($placeholder !== null) {
            $values['hero_search_placeholder'] = $this->clean($placeholder->textContent);
        }

        $subtitle = $xpath->query('//div['.$this->token('share-section-subtitle').']')->item(0);
        if ($subtitle !== null) {
            $values['share_subtitle'] = $this->clean($subtitle->textContent);
        }

        $shareTitle = $xpath->query('//h3['.$this->token('share-section-title').']')->item(0);
        if ($shareTitle !== null) {
            $values['share_title_html'] = trim($this->innerHtml($shareTitle));
        }

        $cards = $xpath->query('//div['.$this->token('share-card').']');
        if ($cards !== false && $cards->length >= 2) {
            $shareCard = $cards->item(0);
            $subscribeCard = $cards->item(1);
            $values['share_card_title'] = $this->clean($xpath->query('.//h2['.$this->token('title').']', $shareCard)->item(0)?->textContent ?? '');
            $values['share_card_text'] = $this->clean($xpath->query('.//p['.$this->token('content').']', $shareCard)->item(0)?->textContent ?? '');
            $values['share_card_button_label'] = $this->clean($xpath->query('.//a', $shareCard)->item(0)?->textContent ?? '');
            $values['subscribe_card_title'] = $this->clean($xpath->query('.//h2['.$this->token('title').']', $subscribeCard)->item(0)?->textContent ?? '');
            $values['subscribe_card_text'] = $this->clean($xpath->query('.//p['.$this->token('content').']', $subscribeCard)->item(0)?->textContent ?? '');
            $values['subscribe_card_button_label'] = $this->clean($xpath->query('.//a', $subscribeCard)->item(0)?->textContent ?? '');
        }

        $shareContent = $xpath->query('//div['.$this->token('share-content').']')->item(0);
        if ($shareContent !== null) {
            $values['share_content_html'] = trim($this->innerHtml($shareContent));
        }

        $footerNote = $xpath->query('//p['.$this->token('hero-brand-disclaimer').']')->item(0);
        if ($footerNote !== null) {
            $values['footer_note'] = $this->clean($footerNote->textContent);
        }

        $featuresTitle = $xpath->query('//section[@id="features-section"]//h3')->item(0);
        if ($featuresTitle !== null) {
            $values['features_title'] = $this->clean($featuresTitle->textContent);
        }

        $order = 0;
        foreach ($values as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            SiteSetting::query()->create([
                'key' => $key,
                'value' => $value,
            ]);
            $order++;
        }
    }

    private function seedHowItWorksSteps(DOMDocument $index): void
    {
        if (HowItWorksStep::query()->exists()) {
            return;
        }

        $xpath = new DOMXPath($index);
        $items = $xpath->query('//div[contains(@class, "how-it-works propose")]//div['.$this->token('how-it-works-item').']');

        if ($items === false) {
            return;
        }

        $order = 0;
        foreach ($items as $item) {
            $title = $xpath->query('.//span['.$this->token('title').']', $item)->item(0);
            $text = $xpath->query('./p', $item)->item(0);
            $icon = $xpath->query('.//svg', $item)->item(0);

            if ($title === null) {
                continue;
            }

            HowItWorksStep::query()->create([
                'title' => $this->clean($title->textContent),
                'text' => $text !== null ? $this->clean($text->textContent) : null,
                'icon_svg' => $icon !== null ? $this->outerHtml($icon) : null,
                'sort_order' => $order,
                'is_active' => true,
            ]);
            $order++;
        }
    }

    private function seedFaqs(DOMDocument $index): void
    {
        if (Faq::query()->exists()) {
            return;
        }

        $xpath = new DOMXPath($index);
        $items = $xpath->query('//section['.$this->token('faq-item').']');

        if ($items === false) {
            return;
        }

        $order = 0;
        foreach ($items as $item) {
            $title = $xpath->query('.//h4['.$this->token('faq-item-title').']', $item)->item(0);
            if ($title === null) {
                continue;
            }

            $parts = [];
            $blocks = $xpath->query('.//div['.$this->token('faq-item-content').']//div['.$this->token('p2-regular').' or '.$this->token('faq-item-subtitle').']', $item);
            if ($blocks !== false) {
                foreach ($blocks as $block) {
                    $text = $this->clean($block->textContent);
                    if ($text !== '') {
                        $parts[] = $text;
                    }
                }
            }

            Faq::query()->create([
                'question' => $this->clean($title->textContent),
                'answer' => implode("\n\n", $parts),
                'sort_order' => $order,
                'is_active' => true,
            ]);
            $order++;
        }
    }

    private function seedTestimonials(DOMDocument $index): void
    {
        if (Testimonial::query()->exists()) {
            return;
        }

        $xpath = new DOMXPath($index);
        $items = $xpath->query('//div[@id="comment-slider"]//div['.$this->token('testimonials-item').']');

        if ($items === false) {
            return;
        }

        $seen = [];
        $order = 0;
        foreach ($items as $item) {
            $author = $xpath->query('.//div['.$this->token('testimonials-item-user').']', $item)->item(0);
            $quote = $xpath->query('.//p['.$this->token('testimonials-item-content').']', $item)->item(0);

            if ($author === null || $quote === null) {
                continue;
            }

            $authorName = $this->clean($author->textContent);
            $quoteText = $this->clean($quote->textContent);
            $key = mb_strtolower($authorName.'|'.$quoteText);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $avatar = $xpath->query('.//img['.$this->token('testimonials-item-avatar').']', $item)->item(0);
            $avatarPath = null;
            if ($avatar !== null) {
                $avatarPath = strtok($avatar->getAttribute('src'), '?') ?: null;
            }

            $stars = $xpath->query('.//img[contains(@src, "full-star.svg")]', $item);
            $rating = $stars !== false && $stars->length > 0 ? $stars->length : null;

            $role = null;
            $footerDivs = $xpath->query('.//div['.$this->token('testimonials-item-footer').']/div', $item);
            if ($footerDivs !== false && $footerDivs->length >= 2) {
                $role = $this->clean($footerDivs->item($footerDivs->length - 1)->textContent);
            }

            Testimonial::query()->create([
                'author_name' => $authorName,
                'role_text' => $role !== '' ? $role : null,
                'quote' => $quoteText,
                'rating' => $rating,
                'avatar_path' => $avatarPath,
                'sort_order' => $order,
                'is_active' => true,
            ]);
            $order++;
        }
    }

    private function seedPressItems(DOMDocument $index): void
    {
        if (PressItem::query()->exists()) {
            return;
        }

        $xpath = new DOMXPath($index);
        $items = $xpath->query('//div[@id="press-slider"]//div['.$this->token('press-item').']');

        if ($items === false) {
            return;
        }

        $order = 0;
        foreach ($items as $item) {
            $img = $xpath->query('.//img', $item)->item(0);
            if ($img === null) {
                continue;
            }

            $name = $this->clean($img->getAttribute('alt'));
            if ($name === '') {
                continue;
            }

            $link = $xpath->query('.//a', $item)->item(0);
            $href = $link !== null ? trim($link->getAttribute('href')) : '';
            if ($href === '' || $href === '#') {
                $href = null;
            }

            PressItem::query()->create([
                'outlet_name' => $name,
                'logo_path' => $img->getAttribute('src') !== '' ? $img->getAttribute('src') : null,
                'logo_text' => null,
                'link_url' => $href,
                'sort_order' => $order,
                'is_active' => true,
            ]);
            $order++;
        }
    }

    private function seedFooter(DOMDocument $index): void
    {
        if (FooterLinkGroup::query()->exists()) {
            return;
        }

        $xpath = new DOMXPath($index);
        $titles = $xpath->query('//footer['.$this->token('sharesub-footer').']//div['.$this->token('footer-link-title').']');

        if ($titles === false) {
            return;
        }

        $order = 0;
        foreach ($titles as $title) {
            $groupTitle = $this->clean($title->textContent);
            if ($groupTitle === '') {
                continue;
            }

            $group = FooterLinkGroup::query()->create([
                'title' => $groupTitle,
                'sort_order' => $order,
            ]);

            $linkOrder = 0;
            $links = $xpath->query('.//div['.$this->token('footer-link').']//a', $title->parentNode);
            if ($links !== false) {
                foreach ($links as $link) {
                    $label = $this->clean($link->textContent);
                    $url = trim($link->getAttribute('href'));
                    if ($label === '' || $url === '') {
                        continue;
                    }
                    FooterLink::query()->create([
                        'footer_link_group_id' => $group->id,
                        'label' => $label,
                        'url' => $url,
                        'sort_order' => $linkOrder,
                        'is_active' => true,
                    ]);
                    $linkOrder++;
                }
            }
            $order++;
        }
    }

    private function seedFeatures(DOMDocument $index): void
    {
        if (Feature::query()->exists()) {
            return;
        }

        $xpath = new DOMXPath($index);
        $items = $xpath->query('//section[@id="features-section"]//div['.$this->token('feature-section-item').']');

        if ($items === false) {
            return;
        }

        $order = 0;
        foreach ($items as $item) {
            $title = $xpath->query('.//div['.$this->token('title').']', $item)->item(0);
            $text = $xpath->query('.//p['.$this->token('content').']', $item)->item(0);
            $img = $xpath->query('.//div['.$this->token('img-wrapper').']//img', $item)->item(0);

            if ($title === null) {
                continue;
            }

            Feature::query()->create([
                'title' => $this->clean($title->textContent),
                'text' => $text !== null ? $this->clean($text->textContent) : null,
                'icon_path' => $img !== null && $img->getAttribute('src') !== '' ? $img->getAttribute('src') : null,
                'icon_svg' => null,
                'sort_order' => $order,
                'is_active' => true,
            ]);
            $order++;
        }
    }

    /**
     * Map brand name (lowercase) to category name from the category cards
     * in subscriptions.html. First card wins when a brand appears twice.
     * Prices there are ignored: the homepage prices are authoritative.
     */
    private function brandCategoryMap(?DOMDocument $subscriptions): array
    {
        $map = [];
        if ($subscriptions === null) {
            return $map;
        }

        $xpath = new DOMXPath($subscriptions);
        $cards = $xpath->query('//div['.$this->token('category-card').']');
        if ($cards === false) {
            return $map;
        }

        foreach ($cards as $card) {
            $title = $xpath->query('.//h2['.$this->token('category-card-title').']//span', $card)->item(0);
            if ($title === null) {
                continue;
            }
            $categoryName = $this->clean($title->textContent);
            if ($categoryName === '') {
                continue;
            }

            $names = $xpath->query('.//a['.$this->token('category-card-item-name').']', $card);
            if ($names === false) {
                continue;
            }
            foreach ($names as $nameNode) {
                $key = mb_strtolower($this->clean($nameNode->textContent));
                if ($key !== '' && ! isset($map[$key])) {
                    $map[$key] = $categoryName;
                }
            }
        }

        return $map;
    }

    /**
     * First sentence of the Amazon detail page lead, or null when the
     * page is missing or the sentence does not fit the column.
     */
    private function amazonShortDescription(?DOMDocument $amazon): ?string
    {
        if ($amazon === null) {
            return null;
        }

        $xpath = new DOMXPath($amazon);
        $lead = $xpath->query('//div['.$this->token('model-header-lead').']')->item(0);
        if ($lead === null) {
            return null;
        }

        $text = $this->clean($lead->textContent);
        $end = strpos($text, '. ');
        $first = $end !== false ? substr($text, 0, $end + 1) : $text;

        return mb_strlen($first) <= 255 ? $first : null;
    }

    private function categorySlug(string $name, string $href): string
    {
        if (preg_match('#/subscriptions/([a-z0-9-]+)\.html#', $href, $matches)) {
            return $matches[1];
        }

        return Str::slug($name);
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

    private function innerHtml(DOMNode $node): string
    {
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument->saveHTML($child);
        }

        return $html;
    }

    private function outerHtml(DOMNode $node): string
    {
        return $node->ownerDocument->saveHTML($node) ?: '';
    }

    private function clean(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\u{2014}", "\u{2013}"], ' - ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text ?? '');
    }
}
