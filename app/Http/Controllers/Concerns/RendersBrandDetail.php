<?php

namespace App\Http\Controllers\Concerns;

use App\Models\BrandFaq;
use App\Models\Page;
use App\Models\ReassuranceItem;
use App\Models\SiteSetting;
use App\Models\Template;
use Illuminate\Support\Facades\Schema;

/**
 * Database driven extras for the brand detail page, rendered from the
 * captured Amazon template in showBrand. A section keeps the captured
 * HTML only when its table has no rows at all (never seeded). When
 * rows exist but none are active, the section renders empty or hides,
 * so deactivated content never comes back as captured HTML. Every
 * setting keeps its captured piece when its key is missing. A missing
 * table never breaks the page: the captured HTML is returned.
 */
trait RendersBrandDetail
{
    /** @var array<string, string|null>|null */
    protected $brandDetailSettings = null;

    /**
     * The checkmark icon copied from the captured reassurance slider.
     * Used when a reassurance item has no icon of its own.
     */
    private const REASSURANCE_CHECK_SVG = '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">'."\n"
        .'    <g clip-path="url(#clip0_659_30319)">'."\n"
        .'        <circle cx="10.0002" cy="9.99998" r="8.54167" fill="none" stroke="currentColor" stroke-width="1.25"/>'."\n"
        .'        <path d="M5.4165 10.7639L8.854 13.8194L14.5832 7.70831" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>'."\n"
        .'    </g>'."\n"
        .'    <defs>'."\n"
        .'        <clipPath id="clip0_659_30319">'."\n"
        .'            <rect width="20" height="20" fill="white"/>'."\n"
        .'        </clipPath>'."\n"
        .'    </defs>'."\n"
        .'</svg>';

    protected function renderBrandDetailExtras(string $html, mixed $brand): string
    {
        if ($brand === null) {
            return $html;
        }

        foreach ([
            'renderBrandFullPrice',
            'renderBrandTrustLine',
            'renderBrandReassurance',
            'renderBrandTemplateTags',
            'renderBrandFilterLabels',
            'renderBrandCategorySidebar',
            'renderBrandOffersIntro',
            'renderBrandNearbyBanner',
            'renderBrandFaqs',
            'renderBrandBlogPosts',
            'renderBrandJsonLd',
        ] as $method) {
            try {
                $html = $this->{$method}($html, $brand);
            } catch (\Throwable) {
                continue;
            }
        }

        return $html;
    }

    protected function brandDetailSetting(string $key): ?string
    {
        if ($this->brandDetailSettings === null) {
            $this->brandDetailSettings = [];

            try {
                if (class_exists(SiteSetting::class) && Schema::hasTable('site_settings')) {
                    $this->brandDetailSettings = SiteSetting::pluck('value', 'key')->all();
                }
            } catch (\Throwable) {
                $this->brandDetailSettings = [];
            }
        }

        $value = $this->brandDetailSettings[$key] ?? null;

        return $value === null || $value === '' ? null : (string) $value;
    }

    protected function activeTemplatesForBrand(int $brandId): mixed
    {
        try {
            if (! class_exists(Template::class) || ! Schema::hasTable('templates')) {
                return collect();
            }

            return Template::where('brand_id', $brandId)->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    protected function activeBrandFaqsForBrand(int $brandId): mixed
    {
        try {
            if (! class_exists(BrandFaq::class) || ! Schema::hasTable('brand_faqs')) {
                return collect();
            }

            return BrandFaq::where('brand_id', $brandId)->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    /**
     * Replace the inner HTML of the div opened by $openTag, starting the
     * search after $anchor when one is given. The matching close tag is
     * found by counting nested divs, like the admin shell does.
     */
    protected function brandDetailReplaceDivInner(string $html, string $openTag, string $inner, ?string $anchor = null): string
    {
        $offset = 0;

        if ($anchor !== null) {
            $anchorPos = strpos($html, $anchor);

            if ($anchorPos === false) {
                return $html;
            }

            $offset = $anchorPos;
        }

        $start = strpos($html, $openTag, $offset);

        if ($start === false) {
            return $html;
        }

        $openEnd = strpos($html, '>', $start);

        if ($openEnd === false) {
            return $html;
        }

        preg_match_all('/<\/?div\b[^>]*>/i', $html, $tags, PREG_OFFSET_CAPTURE, $openEnd + 1);

        $depth = 1;

        foreach ($tags[0] as [$tag, $tagOffset]) {
            $depth += str_starts_with(strtolower($tag), '</div') ? -1 : 1;

            if ($depth === 0) {
                return substr($html, 0, $openEnd + 1)."\n".$inner.substr($html, $tagOffset);
            }
        }

        return $html;
    }

    /**
     * The create offer card shows the full retail price of the brand
     * subscription (Amazon: 7.19) when the brand has one stored, and
     * keeps the captured price otherwise.
     */
    protected function renderBrandFullPrice(string $html, mixed $brand): string
    {
        if (($brand->full_price ?? null) === null) {
            return $html;
        }

        $price = $this->formatPriceAmount((float) $brand->full_price, $this->brandCurrencySymbol($brand));

        return preg_replace_callback(
            '/(<div class="model-header-offer-card model-header-offer-card--create">.*?<strong>)([^<]*)(<small>\/ month<\/small><\/strong>)/s',
            static fn (array $matches): string => $matches[1].e($price).' '.$matches[3],
            $html,
            1,
        ) ?? $html;
    }

    /**
     * The trust line in the brand header, from the brand_trust settings.
     * The rating setting holds both parts separated by a comma, for
     * example "4,6/5, 5200+ reviews on Trustpilot and Google".
     */
    protected function renderBrandTrustLine(string $html, mixed $brand): string
    {
        $ratingText = $this->brandDetailSetting('brand_trust_rating_text');

        if ($ratingText !== null) {
            // The rating itself uses a comma decimal (4,9/5), so split
            // after the /5, not on the first comma of the value.
            if (preg_match('/^(.+?\/5)\s*,\s*(.*)$/s', $ratingText, $split)) {
                $rating = trim($split[1]);
                $rest = trim($split[2]);
            } else {
                $parts = explode(',', $ratingText, 2);
                $rating = trim($parts[0]);
                $rest = isset($parts[1]) ? trim($parts[1]) : '';
            }

            $html = preg_replace_callback(
                '/(<div class="model-header-trust">\s*<span><strong>)[^<]*(<\/strong>)[^<]*(<\/span>)/s',
                static function (array $matches) use ($rating, $rest): string {
                    return $matches[1].e($rating).' ★&nbsp;'.$matches[2].e($rest).$matches[3];
                },
                $html,
                1,
            ) ?? $html;
        }

        $usersText = $this->brandDetailSetting('brand_trust_users_text');

        if ($usersText !== null) {
            $html = preg_replace_callback(
                '/(<div class="model-header-trust">.*?<span><strong>[^<]*<\/strong>[^<]*<\/span>\s*<span><strong>)[^<]*(<\/strong><\/span>)/s',
                static fn (array $matches): string => $matches[1].e($usersText).' '.$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }

    /**
     * The reassurance slider, rendered from reassurance_items with the
     * exact captured markup. The captured first item names the brand,
     * so that name is swapped for the current brand when rendering.
     * The captured slider is kept only when the table is empty.
     */
    protected function renderBrandReassurance(string $html, mixed $brand): string
    {
        try {
            if (! class_exists(ReassuranceItem::class) || ! Schema::hasTable('reassurance_items')) {
                return $html;
            }

            $items = ReassuranceItem::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return $html;
        }

        if ($items->isEmpty() && ! $this->tableHasRows('reassurance_items')) {
            return $html;
        }

        $name = (string) $brand->name;
        $rendered = '';

        foreach ($items as $item) {
            $icon = ! empty($item->icon) ? (string) $item->icon : self::REASSURANCE_CHECK_SVG;
            $text = str_replace('Amazon', $name, (string) $item->text);

            $rendered .= '<div class="splide__slide model-reassurance-item">'."\n"
                .'<div class="model-reassurance-item-title">'."\n"
                .$icon.' '.e((string) $item->title).' </div>'."\n"
                .'<div class="model-reassurance-item-text">'."\n"
                .e($text).' </div>'."\n"
                .'</div>'."\n";
        }

        return $this->brandDetailReplaceDivInner(
            $html,
            '<div class="splide__list model-reassurance-grid">',
            $rendered,
        );
    }

    /**
     * The template filter tags in the sidebar, rendered from the
     * brand's active templates. The "All" tag stays first. The
     * captured tags are kept only when the templates table has no rows
     * at all; when templates exist but none are active for this brand,
     * the tags render empty.
     */
    protected function renderBrandTemplateTags(string $html, mixed $brand): string
    {
        $templates = $this->activeTemplatesForBrand((int) $brand->id);

        if ($templates->isEmpty() && ! $this->tableHasRows('templates')) {
            return $html;
        }

        $tags = '';

        if ($templates->isNotEmpty()) {
            $tags = '<a class="filter-tag active " href="'.e($this->brandUrl($brand)).'">'."\n"
                .'<span>All</span>'."\n"
                .'</a>'."\n";

            foreach ($templates as $template) {
                $tags .= '<span class="cursor-pointer filter-tag">'."\n"
                    .'<span>'.e((string) $template->name).'</span>'."\n"
                    .'</span>'."\n";
            }
        }

        return $this->brandDetailReplaceDivInner(
            $html,
            '<div class="filter-tags ">',
            $tags,
            '<div class="filter-tags-wrapper filter-tags-sidebar">',
        );
    }

    /**
     * The Filters and Browse labels and the three filter toggles, from
     * the brand_filter, brand_browse and brand_toggle settings. Each
     * piece keeps its captured text when its key is missing.
     */
    protected function renderBrandFilterLabels(string $html, mixed $brand): string
    {
        $filterLabel = $this->brandDetailSetting('brand_filter_label');
        $browseLabel = $this->brandDetailSetting('brand_browse_label');

        if ($filterLabel !== null) {
            $html = preg_replace_callback(
                '/(<span class="filter-header-title filter-header-title--search">)[^<]*(<\/span>)/',
                static fn (array $matches): string => $matches[1].e($filterLabel).$matches[2],
                $html,
                1,
            ) ?? $html;

            $html = preg_replace_callback(
                '/(<button type="button" class="result-quick-filter" data-filter="search">.*?)Filters(\s*<\/button>)/s',
                static fn (array $matches): string => $matches[1].e($filterLabel).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        if ($browseLabel !== null) {
            $html = preg_replace_callback(
                '/(<span class="filter-header-title filter-header-title--browse">)[^<]*(<\/span>)/',
                static fn (array $matches): string => $matches[1].e($browseLabel).$matches[2],
                $html,
                1,
            ) ?? $html;

            $html = preg_replace_callback(
                '/(<button type="button" class="result-quick-filter" data-filter="browse">.*?)Browse(\s*<\/button>)/s',
                static fn (array $matches): string => $matches[1].e($browseLabel).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $toggles = [
            'energy_icon.svg' => ['brand_toggle_access_title', 'brand_toggle_access_text'],
            'identity_icon.svg' => ['brand_toggle_identity_title', 'brand_toggle_identity_text'],
            'invoice.svg' => ['brand_toggle_invoice_title', 'brand_toggle_invoice_text'],
        ];

        foreach ($toggles as $icon => $keys) {
            $title = $this->brandDetailSetting($keys[0]);
            $text = $this->brandDetailSetting($keys[1]);
            $iconPattern = preg_quote('/img/browse/'.$icon, '/');

            if ($title !== null) {
                $html = preg_replace_callback(
                    '/(<img src="'.$iconPattern.'"[^>]*>.*?<div class="filter-toggle-title">)[^<]*(<\/div>)/s',
                    static fn (array $matches): string => $matches[1].e($title).$matches[2],
                    $html,
                    1,
                ) ?? $html;
            }

            if ($text !== null) {
                $html = preg_replace_callback(
                    '/(<img src="'.$iconPattern.'"[^>]*>.*?<div class="filter-toggle-text">)[^<]*(<\/div>)/s',
                    static fn (array $matches): string => $matches[1].e($text).$matches[2],
                    $html,
                    1,
                ) ?? $html;
            }
        }

        return $html;
    }

    /**
     * The browse sidebar: the category tags from the categories table,
     * with the brand's own category active, and the brand tags for the
     * brand's category from the brands table. The captured sidebar is
     * kept only when the categories table has no rows at all, and the
     * captured brand tags stay only when the brands table is empty.
     */
    protected function renderBrandCategorySidebar(string $html, mixed $brand): string
    {
        $categories = $this->activeCategories();

        if ($categories->isEmpty() && ! $this->tableHasRows('categories')) {
            return $html;
        }

        $tags = '';

        foreach ($categories as $category) {
            $active = $brand->category_id !== null && (int) $category->id === (int) $brand->category_id ? ' active' : '';
            $href = $category->slug === 'house' ? '/en/subscriptions/house.html' : '#offline';
            $tags .= '<a href="'.$href.'" class="filter-tag'.$active.'">'."\n"
                .((string) ($category->icon_svg ?? ''))."\n"
                .'<span>'.e((string) $category->name).'</span>'."\n"
                .'</a>'."\n";
        }

        $html = $this->brandDetailReplaceDivInner(
            $html,
            '<div class="filter-tags">',
            $tags,
            '<div class="filter-section-title">Categories</div>',
        );

        if ($brand->category_id === null) {
            return $html;
        }

        $categoryBrands = $this->activeBrandsForCategory((int) $brand->category_id);

        if ($categoryBrands->isEmpty() && ! $this->tableHasRows('brands')) {
            return $html;
        }

        $category = null;

        try {
            $category = $brand->category;
        } catch (\Throwable) {
            $category = null;
        }

        $categoryName = $category !== null ? (string) $category->name : '';

        $html = preg_replace_callback(
            '/(<div class="filter-section-title">Brands > )[^<]*(<\/div>)/',
            static fn (array $matches): string => $matches[1].e($categoryName).$matches[2],
            $html,
            1,
        ) ?? $html;

        $brandTags = '';

        foreach ($categoryBrands as $categoryBrand) {
            $active = (int) $categoryBrand->id === (int) $brand->id ? ' active' : '';
            $brandTags .= '<a href="'.e($this->brandUrl($categoryBrand)).'" class="filter-tag'.$active.'">'."\n"
                .'<span>'.e((string) $categoryBrand->name).'</span>'."\n"
                .'</a>'."\n";
        }

        return $this->brandDetailReplaceDivInner(
            $html,
            '<div class="filter-tags">',
            $brandTags,
            '<div class="filter-section-title">Brands > ',
        );
    }

    /**
     * The offers heading intro under the brand seats title, from the
     * brand_offers_intro setting with the {brand} token replaced.
     */
    protected function renderBrandOffersIntro(string $html, mixed $brand): string
    {
        $intro = $this->brandDetailSetting('brand_offers_intro');

        if ($intro === null) {
            return $html;
        }

        $text = str_replace('{brand}', (string) $brand->name, $intro);

        return preg_replace_callback(
            '/(<p class="result-places-text">).*?(<\/p>)/s',
            static fn (array $matches): string => $matches[1].e($text).$matches[2],
            $html,
            1,
        ) ?? $html;
    }

    /**
     * The nearby subscriptions banner inside the offers list, from the
     * brand_nearby_title and brand_nearby_text settings.
     */
    protected function renderBrandNearbyBanner(string $html, mixed $brand): string
    {
        $title = $this->brandDetailSetting('brand_nearby_title');

        if ($title !== null) {
            $html = preg_replace_callback(
                '/(<span class=\'p2-bold\'>).*?(<\/span>)/s',
                static fn (array $matches): string => $matches[1].e($title).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $text = $this->brandDetailSetting('brand_nearby_text');

        if ($text !== null) {
            $html = preg_replace_callback(
                '/(<div class=\'p3-regular\'>).*?(<\/div>)/s',
                static fn (array $matches): string => $matches[1].e($text).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }

    /**
     * The before joining section, rendered from the brand's FAQs with
     * the title and intro from the brand_before_join settings. The
     * captured section is kept only when the brand_faqs table has no
     * rows at all; when FAQs exist but none are active for this brand,
     * the section is hidden. The captured first question names the
     * brand, so that name is swapped for the current brand when
     * rendering.
     */
    protected function renderBrandFaqs(string $html, mixed $brand): string
    {
        $faqs = $this->activeBrandFaqsForBrand((int) $brand->id);

        if ($faqs->isEmpty()) {
            if (! $this->tableHasRows('brand_faqs')) {
                return $html;
            }

            return preg_replace(
                '/<section class="brand-before-join">.*?<\/section>/s',
                '',
                $html,
                1,
            ) ?? $html;
        }

        $name = (string) $brand->name;
        $title = $this->brandDetailSetting('brand_before_join_title');
        $title = $title !== null ? str_replace('{brand}', $name, $title) : 'Before joining a group '.$name;
        $intro = $this->brandDetailSetting('brand_before_join_intro');
        $intro = $intro !== null ? str_replace('{brand}', $name, $intro) : 'Co-subscriptions are easy to understand, but it\'s a good idea to check how they work before you get started. Here are the answers to the most frequently asked questions.';

        $cards = '';

        foreach ($faqs as $faq) {
            $question = str_replace('Amazon', $name, (string) $faq->question);
            $answer = str_replace('Amazon', $name, (string) $faq->answer);

            $cards .= '<article class="brand-before-join-card">'."\n"
                .'<h3 class="brand-before-join-card-title">'."\n"
                .'<span class="brand-before-join-check" aria-hidden="true">✓</span> '.e($question).' </h3>'."\n"
                .'<p class="brand-before-join-card-text">'.e($answer).'</p>'."\n"
                .'</article>'."\n";
        }

        $section = '<section class="brand-before-join">'."\n"
            .'<h2 class="brand-before-join-title">'.e($title).'</h2>'."\n"
            .'<p class="brand-before-join-intro">'.e($intro).'</p>'."\n"
            .'<div class="brand-before-join-grid">'."\n"
            .$cards
            .'</div>'."\n"
            .'</section>';

        return preg_replace_callback(
            '/<section class="brand-before-join">.*?<\/section>/s',
            static fn (array $matches): string => $section,
            $html,
            1,
        ) ?? $html;
    }

    /**
     * The blog posts block, rendered from active post type Page
     * records whose title contains the brand name. The whole block is
     * hidden when no post matches.
     */
    protected function renderBrandBlogPosts(string $html, mixed $brand): string
    {
        if (! str_contains($html, 'brand-reinsurance')) {
            return $html;
        }

        try {
            if (! class_exists(Page::class) || ! Schema::hasTable('pages')) {
                return $html;
            }

            $name = (string) $brand->name;
            $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $name);
            $posts = Page::where('type', 'post')->where('is_active', true)
                ->where('title', 'like', '%'.$like.'%')
                ->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return $html;
        }

        if ($posts->isEmpty()) {
            return preg_replace(
                '/<section class="brand-reinsurance">.*?<\/section>\s*<\/div>\s*<\/section>/s',
                '',
                $html,
                1,
            ) ?? $html;
        }

        $items = '';

        foreach ($posts as $post) {
            $items .= '<li><a href="/cms/en/'.e((string) $post->slug).'">'.e((string) $post->title).'</a></li>'."\n";
        }

        $groups = '<div class="article-group">'."\n"
            .'<h3>'.e((string) $brand->name).'</h3>'."\n"
            .'<ul>'."\n"
            .$items
            .'</ul>'."\n"
            .'</div>'."\n";

        return $this->brandDetailReplaceDivInner(
            $html,
            '<div class="article-groups">',
            $groups,
        );
    }

    /**
     * The SoftwareApplication JSON-LD rating, from the
     * jsonld_rating_value and jsonld_rating_count settings. Each value
     * keeps its captured number when its key is missing.
     */
    protected function renderBrandJsonLd(string $html, mixed $brand): string
    {
        $value = $this->brandDetailSetting('jsonld_rating_value');

        if ($value !== null) {
            $html = preg_replace_callback(
                '/("ratingValue":\s*")[^"]*(")/',
                static fn (array $matches): string => $matches[1].e($value).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $count = $this->brandDetailSetting('jsonld_rating_count');

        if ($count !== null) {
            $html = preg_replace_callback(
                '/("ratingCount":\s*")[^"]*(")/',
                static fn (array $matches): string => $matches[1].e($count).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }
}
