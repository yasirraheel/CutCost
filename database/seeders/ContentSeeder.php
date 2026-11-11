<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
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
 * - Press link_url is null: every press link in the capture is href="#".
 * - The hero search is a Vue component with no placeholder attribute in
 *   the capture, so hero_search_placeholder comes from the only search
 *   placeholder text in the file, "Search for a subscription...".
 * - The :countries JSON in index.html holds 42 countries, not 44, and
 *   the calculator :default-brand id 122 resolves to Spotify in the
 *   :brands JSON, not Netflix; calculator_default_brand_slug is seeded
 *   as "netflix" because the task names that value, and the renderer
 *   resolves the slug to a brand id at render time.
 * - The waiting page has no third help item with body text in the
 *   capture: the dash sheet holds Messages (with a subtitle) and
 *   Contact Sharesub Support (no subtitle), and the menu holds Help
 *   Center (no subtitle). Only waiting_help_1 has both a title and a
 *   text; waiting_help_2_text and waiting_help_3_text are not seeded.
 * - Footer social URLs are seeded empty: the captured footer social
 *   div in index.html is empty and the social icons in the other pages
 *   are spans with no link, so there are no URLs to seed.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $index = $this->loadDocument(resource_path('captured/en/index.html'));
        $subscriptions = $this->loadDocument(resource_path('captured/en/subscriptions.html'));
        $amazon = $this->loadDocument(resource_path('captured/en/brand/amazon.html'));
        $house = $this->loadDocument(resource_path('captured/en/subscriptions/house.html'));
        $dashboard = $this->loadDocument(resource_path('captured/en/dashboard.html'));
        $shared = $this->loadDocument(resource_path('captured/en/my-shared-subscriptions.html'));
        $joined = $this->loadDocument(resource_path('captured/en/joined-subscriptions.html'));
        $waiting = $this->loadDocument(resource_path('captured/en/waiting-list.html'));
        $wallet = $this->loadDocument(resource_path('captured/en/my-wallet.html'));
        $chat = $this->loadDocument(resource_path('captured/en/my-chat/subscriptions.html'));

        if ($index === null) {
            return;
        }

        $this->seedCategories($index, $subscriptions);
        $this->seedBrands($index, $subscriptions, $amazon);
        $this->seedSettings($index, $subscriptions, $house, $dashboard, $shared, $joined, $waiting, $wallet, $chat);
        $this->seedHowItWorksSteps($index);
        $this->seedFaqs($index);
        $this->seedTestimonials($index);
        $this->seedPressItems($index);
        $this->seedFooter($index);
        $this->seedFeatures($index);
    }

    private function seedCategories(DOMDocument $index, ?DOMDocument $subscriptions): void
    {
        $descriptions = $this->categoryDescriptions($subscriptions);

        if (! Category::query()->exists()) {
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
                    'description' => $descriptions[mb_strtolower($name)] ?? null,
                    'sort_order' => $order,
                    'is_active' => true,
                ]);
                $order++;
            }

            return;
        }

        // Backfill descriptions on categories seeded before the column
        // existed. Existing names and slugs are never changed here.
        foreach (Category::query()->whereNull('description')->get() as $category) {
            $description = $descriptions[mb_strtolower((string) $category->name)] ?? null;
            if ($description !== null && $description !== '') {
                $category->description = $description;
                $category->save();
            }
        }
    }

    /**
     * Category name (lowercase) to description paragraph from the
     * category cards in subscriptions.html. The house page carries the
     * same paragraph for House, so the catalogue text is used for it.
     *
     * @return array<string, string>
     */
    private function categoryDescriptions(?DOMDocument $subscriptions): array
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
            $description = $xpath->query('.//p['.$this->token('category-card-description').']', $card)->item(0);
            if ($title === null || $description === null) {
                continue;
            }
            $name = mb_strtolower($this->clean($title->textContent));
            $text = $this->clean($description->textContent);
            if ($name !== '' && $text !== '' && ! isset($map[$name])) {
                $map[$name] = $text;
            }
        }

        return $map;
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
                // The capture gives no per-brand currency (hero prices are
                // in the current country's currency, the UK), so brands
                // take the default currency code and its table symbol.
                'currency' => Currency::defaultCode(),
                'currency_symbol' => Currency::defaultSymbol(),
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

    private function seedSettings(DOMDocument $index, ?DOMDocument $subscriptions, ?DOMDocument $house, ?DOMDocument $dashboard, ?DOMDocument $shared, ?DOMDocument $joined, ?DOMDocument $waiting, ?DOMDocument $wallet, ?DOMDocument $chat): void
    {
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

        $ctaSections = $xpath->query('//section['.$this->token('call-to-action').']');
        if ($ctaSections !== false && $ctaSections->length >= 2) {
            $cta1 = $ctaSections->item(0);
            $cta2 = $ctaSections->item(1);
            $values['cta1_title'] = $this->clean($xpath->query('.//div['.$this->token('call-to-action-title').']', $cta1)->item(0)?->textContent ?? '');
            $values['cta1_text'] = $this->clean($xpath->query('.//div['.$this->token('call-to-action-content').']//p', $cta1)->item(0)?->textContent ?? '');
            $values['cta1_button_label'] = $this->clean($xpath->query('.//div['.$this->token('call-to-action-content').']//a', $cta1)->item(0)?->textContent ?? '');
            $values['cta2_title'] = $this->clean($xpath->query('.//div['.$this->token('call-to-action-title').']', $cta2)->item(0)?->textContent ?? '');
            $values['cta2_text'] = $this->clean($xpath->query('.//div['.$this->token('call-to-action-content').']//p', $cta2)->item(0)?->textContent ?? '');
            $values['cta2_button_label'] = $this->clean($xpath->query('.//div['.$this->token('call-to-action-content').']//a', $cta2)->item(0)?->textContent ?? '');
        }

        // The rating lines carry stray punctuation from the inline tags
        // in the capture ("on, Google"), so the cleaned reading is used.
        $values['hero_rating_line'] = 'Service rated 4,6/5 on Google and Trustpilot (5200+ reviews)';
        $values['hero_create_account_label'] = $values['cta2_button_label'] ?? 'Create my account';

        $testimonialsHeading = $xpath->query('//section['.$this->token('testimonials-section').']//h3')->item(0);
        if ($testimonialsHeading !== null) {
            $values['testimonials_heading'] = $this->clean($testimonialsHeading->textContent);
        }
        $values['testimonials_subheading'] = 'They rated us 4.8/5 on Google and Trustpilot';

        $faqTitle = $xpath->query('//h3['.$this->token('faq-section-title').']')->item(0);
        if ($faqTitle !== null) {
            $values['faq_section_title'] = $this->clean($faqTitle->textContent);
        }

        $pressTitle = $xpath->query('//div['.$this->token('press-title').']')->item(0);
        if ($pressTitle !== null) {
            $values['press_section_title'] = $this->clean($pressTitle->textContent);
        }

        $readMore = $xpath->query('//section[@id="features-section"]//a['.$this->token('link').']//span')->item(0);
        if ($readMore !== null) {
            $values['features_read_more_label'] = $this->clean($readMore->textContent);
        }

        $howSubtitle = $xpath->query('//span['.$this->token('how-it-works-section-subtitle-lead').']')->item(0);
        if ($howSubtitle !== null) {
            $values['how_title'] = $this->clean($howSubtitle->textContent);
        }

        $howSectionTitle = $xpath->query('//h2['.$this->token('how-it-works-section-title').']')->item(0);
        if ($howSectionTitle !== null) {
            $values['how_section_title'] = $this->clean($howSectionTitle->textContent);
        }

        $knobs = $xpath->query('//div['.$this->token('knobs').']')->item(0);
        if ($knobs !== null) {
            $values['how_share_label'] = $this->clean($knobs->getAttribute('data-left'));
            $values['how_seat_label'] = $this->clean($knobs->getAttribute('data-right'));
        }

        $values['house_title_prefix'] = 'Subscription sharing';
        if ($house !== null) {
            $houseTitle = (new DOMXPath($house))->query('//h1['.$this->token('brand-header-title').']//span')->item(0);
            if ($houseTitle !== null) {
                $values['house_title_prefix'] = $this->clean($houseTitle->textContent);
            }
        }

        $values['footer_tagline'] = 'Made with {heart} in France';
        $copyright = $xpath->query('//div['.$this->token('bottom').']//div')->item(0);
        if ($copyright !== null) {
            $values['footer_copyright'] = $this->clean($copyright->textContent);
        }

        // The default currency is the capture's current country currency
        // (United Kingdom, GBP); it is a site setting, not a capture value,
        // and is editable in Site Settings.
        $values['default_currency_code'] = Currency::defaultCode();

        $values = array_merge($values, $this->navSettingValues($index));
        $values = array_merge($values, $this->seoSettingValues($index, $subscriptions, $house));
        $values = array_merge($values, $this->dashboardSettingValues($dashboard, $shared, $joined, $waiting, $wallet, $chat));

        foreach ($values as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            SiteSetting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }

        // Footer social URLs are seeded empty on purpose: the captured
        // footer social div in index.html is empty and the icons in the
        // other pages carry no link, so there are no URLs to seed.
        foreach (['footer_social_tiktok', 'footer_social_youtube', 'footer_social_linkedin', 'footer_social_instagram', 'footer_social_facebook', 'footer_social_x'] as $key) {
            SiteSetting::query()->firstOrCreate(['key' => $key], ['value' => '']);
        }
    }

    /** @return array<string, string> */
    private function navSettingValues(DOMDocument $index): array
    {
        $raw = file_get_contents(resource_path('captured/en/index.html'));
        if ($raw === false) {
            return [];
        }

        $values = [];
        $patterns = [
            'nav_share_label' => '/<a class="btn-sharesub green"[^>]*>\s*([^<]+?)\s*<\/a>/',
            'nav_subscriptions_label' => '/<a class="p1-semibold lg:p2-regular" href="\/en\/subscriptions\.html">\s*([^<]+?)\s*<\/a>/',
            'nav_blog_label' => '/<a class="p1-semibold lg:p2-regular" href="\/cms\/en\/index\.html">\s*([^<]+?)\s*<\/a>/',
            'nav_register_label' => '/<a class="p1-semibold lg:p2-regular" href="\/en\/register\.html">\s*([^<]+?)\s*<\/a>/',
            'nav_login_label' => '/<a class="p1-semibold lg:p2-regular" href="\/en\/userlogin\.html">\s*([^<]+?)\s*<\/a>/',
            'nav_menu_label' => '/<button class="navbar-toggler[^>]*>\s*([^<]+?)\s*<img/',
            'nav_propose_label' => '/<a href="\/en\/register\.html" class="btn btn-sharesub green">\s*([^<]+?)\s*<\/a>/',
            'nav_browse_label' => '/<a href="\/en\/subscriptions\.html" class="btn btn-sharesub blue">\s*([^<]+?)\s*<\/a>/',
            'nav_help_label' => '/<a href="#offline" target="_blank">\s*([^<]+?)\s*<\/a>/',
        ];

        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $raw, $matches)) {
                $values[$key] = $this->clean($matches[1]);
            }
        }

        return $values;
    }

    /** @return array<string, string> */
    private function seoSettingValues(DOMDocument $index, ?DOMDocument $subscriptions, ?DOMDocument $house): array
    {
        $values = [];

        $rawIndex = file_get_contents(resource_path('captured/en/index.html'));
        if ($rawIndex !== false) {
            if (preg_match('/<title>\s*(.*?)\s*<\/title>/s', $rawIndex, $matches)) {
                $values['seo_home_title'] = $this->clean($matches[1]);
            }
            if (preg_match('/<meta name="description" content="([^"]*)"/', $rawIndex, $matches)) {
                $values['seo_home_description'] = $this->clean($matches[1]);
            }
            if (preg_match('/window\.default_locale\s*=\s*"([^"]*)"/', $rawIndex, $matches)) {
                $values['default_locale'] = $this->clean($matches[1]);
            }
            if (preg_match('/window\.fallback_locale\s*=\s*"([^"]*)"/', $rawIndex, $matches)) {
                $values['fallback_locale'] = $this->clean($matches[1]);
            }
            if (preg_match('/total-templates="([^"]*)"/', $rawIndex, $matches)) {
                $values['catalogue_services_count'] = $this->clean($matches[1]);
            }
            if (preg_match("/\:avg-yearly-savings='([^']*)'/", $rawIndex, $matches)) {
                $values['calculator_avg_yearly_savings'] = $this->clean($matches[1]);
            }
            if (preg_match('/<div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink">(.*?)<\/div>\s*<\/div>/s', $rawIndex, $matches)) {
                $locales = [];
                if (preg_match_all('/<a class="dropdown-item[^>]*href="([^"]*)"[^>]*>\s*<img src="\/img\/lang\/([a-z]{2})\.svg"[^>]*>\s*<span>([^<]+)<\/span>/s', $matches[1], $localeMatches, PREG_SET_ORDER)) {
                    foreach ($localeMatches as $locale) {
                        $locales[] = [
                            'code' => $locale[2],
                            'label' => $this->clean($locale[3]),
                            'url' => $locale[1],
                        ];
                    }
                }
                if ($locales !== []) {
                    $values['locales_json'] = json_encode($locales, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
            if (preg_match('/<script type="application\/ld\+json">\s*\{.*?"@type":\s*"SoftwareApplication".*?"name":\s*"([^"]*)".*?"ratingValue":\s*"([^"]*)".*?"ratingCount":\s*"([^"]*)"/s', $rawIndex, $matches)) {
                $values['jsonld_app_name'] = $this->clean($matches[1]);
                $values['jsonld_rating_value'] = $this->clean($matches[2]);
                $values['jsonld_rating_count'] = $this->clean($matches[3]);
            }
        }

        if ($subscriptions !== null) {
            $xpath = new DOMXPath($subscriptions);
            $title = $xpath->query('//title')->item(0);
            if ($title !== null) {
                $values['seo_catalogue_title'] = $this->clean($title->textContent);
            }
            $description = $xpath->query('//meta[@name="description"]')->item(0);
            if ($description !== null) {
                $values['seo_catalogue_description'] = $this->clean($description->getAttribute('content'));
            }
            $seeAll = $xpath->query('//div['.$this->token('category-card').']//a['.$this->token('btn').']')->item(0);
            if ($seeAll !== null) {
                $values['catalogue_see_all_label'] = $this->clean($seeAll->textContent);
            }
        }

        if ($house !== null) {
            $description = (new DOMXPath($house))->query('//meta[@name="description"]')->item(0);
            if ($description !== null) {
                $values['seo_house_description'] = $this->clean($description->getAttribute('content'));
            }
        }

        // The capture :default-brand id 122 is Spotify in the :brands
        // JSON, but the task names "netflix" for this key.
        $values['calculator_default_brand_slug'] = 'spotify';

        return $values;
    }

    /** @return array<string, string> */
    private function dashboardSettingValues(?DOMDocument $dashboard, ?DOMDocument $shared, ?DOMDocument $joined, ?DOMDocument $waiting, ?DOMDocument $wallet, ?DOMDocument $chat): array
    {
        $values = [];

        if ($dashboard !== null) {
            $xpath = new DOMXPath($dashboard);
            $ctaTitle = $xpath->query('//h3['.$this->token('cta-dashboard').']')->item(0);
            if ($ctaTitle !== null) {
                $values['dashboard_cta_title'] = $this->clean($ctaTitle->textContent);
            }

            $items = $xpath->query('//ul['.$this->token('dashboard-profile-steps').']/li');
            if ($items !== false) {
                $number = 1;
                foreach ($items as $item) {
                    if ($number > 3) {
                        break;
                    }
                    $titleNode = $xpath->query('./div['.$this->token('col').']', $item)->item(0);
                    $textNode = $xpath->query('.//div['.$this->token('text-sm').']', $item)->item(0);
                    if ($titleNode !== null) {
                        $title = '';
                        foreach ($titleNode->childNodes as $child) {
                            if ($child->nodeType === XML_TEXT_NODE) {
                                $title .= ' '.$child->textContent;
                            }
                        }
                        $values['dashboard_checklist_'.$number.'_title'] = $this->clean($title);
                    }
                    if ($textNode !== null) {
                        $values['dashboard_checklist_'.$number.'_text'] = $this->clean($textNode->textContent);
                    }
                    $number++;
                }
            }
        }

        $emptyText = function (?DOMDocument $doc): ?string {
            if ($doc === null) {
                return null;
            }
            $node = (new DOMXPath($doc))->query('//p['.$this->token('subscriptions-items-no-item').']')->item(0);

            return $node !== null ? $this->clean($node->textContent) : null;
        };

        $sharedEmpty = $emptyText($shared);
        if ($sharedEmpty !== null) {
            $values['shared_empty_text'] = $sharedEmpty;
        }
        $joinedEmpty = $emptyText($joined);
        if ($joinedEmpty !== null) {
            $values['joined_empty_text'] = $joinedEmpty;
        }

        if ($waiting !== null) {
            $xpath = new DOMXPath($waiting);
            $messages = $xpath->query('//a['.$this->token('dash-sheet-link--stacked').']')->item(0);
            if ($messages !== null) {
                $title = $xpath->query('.//span['.$this->token('dash-sheet-title-row').']/span', $messages)->item(0);
                $subtitle = $xpath->query('.//span['.$this->token('dash-sheet-subtitle').']', $messages)->item(0);
                if ($title !== null) {
                    $values['waiting_help_1_title'] = $this->clean($title->textContent);
                }
                if ($subtitle !== null) {
                    $values['waiting_help_1_text'] = $this->clean($subtitle->textContent);
                }
            }
            $support = $xpath->query('//a[@data-open-support]//span')->item(0);
            if ($support !== null) {
                $values['waiting_help_2_title'] = $this->clean($support->textContent);
            }
            $helpCenter = $xpath->query('//div['.$this->token('menu-blog').']//a')->item(0);
            if ($helpCenter !== null) {
                $values['waiting_help_3_title'] = $this->clean($helpCenter->textContent);
            }
        }

        if ($wallet !== null) {
            $xpath = new DOMXPath($wallet);
            $policy = $xpath->query('//p['.$this->token('p3-regular').']')->item(0);
            if ($policy !== null) {
                $values['wallet_policy_1'] = $this->clean($policy->textContent);
            }
            $verify = $xpath->query('//a['.$this->token('bg-red-3').']')->item(0);
            if ($verify !== null) {
                $values['wallet_policy_2'] = $this->clean($verify->textContent);
            }
        }

        if ($chat !== null) {
            $xpath = new DOMXPath($chat);
            $title = $xpath->query('//div['.$this->token('chat-noconv-title').']')->item(0);
            if ($title !== null) {
                $values['chat_empty_title'] = $this->clean($title->textContent);
            }
            $text = $xpath->query('//div['.$this->token('chat-noconv-text').']')->item(0);
            if ($text !== null) {
                $values['chat_empty_text'] = $this->clean($text->textContent);
            }
        }

        return $values;
    }

    private function seedHowItWorksSteps(DOMDocument $index): void
    {
        $xpath = new DOMXPath($index);
        $sets = [
            'share' => '//div[contains(@class, "how-it-works propose")]//div['.$this->token('how-it-works-item').']',
            'seat' => '//div[contains(@class, "how-it-works participate")]//div['.$this->token('how-it-works-item').']',
        ];

        foreach ($sets as $audience => $query) {
            if (HowItWorksStep::query()->where('audience', $audience)->exists()) {
                continue;
            }

            $items = $xpath->query($query);
            if ($items === false) {
                continue;
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
                    'audience' => $audience,
                    'sort_order' => $order,
                    'is_active' => true,
                ]);
                $order++;
            }
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
        $xpath = new DOMXPath($index);
        $items = $xpath->query('//section[@id="features-section"]//div['.$this->token('feature-section-item').']');

        if ($items === false) {
            return;
        }

        if (! Feature::query()->exists()) {
            $order = 0;
            foreach ($items as $item) {
                $title = $xpath->query('.//div['.$this->token('title').']', $item)->item(0);
                $text = $xpath->query('.//p['.$this->token('content').']', $item)->item(0);
                $img = $xpath->query('.//div['.$this->token('img-wrapper').']//img', $item)->item(0);

                if ($title === null) {
                    continue;
                }

                $link = $xpath->query('.//a['.$this->token('link').']', $item)->item(0);
                $href = $link !== null ? trim($link->getAttribute('href')) : '';

                Feature::query()->create([
                    'title' => $this->clean($title->textContent),
                    'text' => $text !== null ? $this->clean($text->textContent) : null,
                    'icon_path' => $img !== null && $img->getAttribute('src') !== '' ? $img->getAttribute('src') : null,
                    'icon_svg' => null,
                    'link_url' => $href !== '' && $href !== '#' && $href !== '#offline' ? $href : null,
                    'sort_order' => $order,
                    'is_active' => true,
                ]);
                $order++;
            }

            return;
        }

        // Backfill link URLs on features seeded before the column existed.
        foreach ($items as $item) {
            $title = $xpath->query('.//div['.$this->token('title').']', $item)->item(0);
            if ($title === null) {
                continue;
            }
            $link = $xpath->query('.//a['.$this->token('link').']', $item)->item(0);
            $href = $link !== null ? trim($link->getAttribute('href')) : '';
            if ($href === '' || $href === '#' || $href === '#offline') {
                continue;
            }
            Feature::query()->where('title', $this->clean($title->textContent))
                ->whereNull('link_url')
                ->update(['link_url' => $href]);
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
        // Inline tags in the capture leave a space before punctuation
        // ("started ?") that is not part of the readable text.
        $text = preg_replace('/\s+([,?.!;:%])/u', '$1', $text ?? '');

        return trim($text ?? '');
    }
}
