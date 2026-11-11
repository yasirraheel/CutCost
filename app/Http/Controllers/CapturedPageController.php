<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Feature;
use App\Models\FooterLinkGroup;
use App\Models\KycSubmission;
use App\Models\PressItem;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CapturedPageController extends Controller
{
    /**
     * Pages that came from the supplied static capture and are now protected
     * by Laravel's session guard.
     *
     * @var array<string, string>
     */
    public const PROTECTED_PAGES = [
        'en/dashboard.html' => 'Dashboard',
        'en/my-shared-subscriptions.html' => 'Shared subscriptions',
        'en/joined-subscriptions.html' => 'Joined subscriptions',
        'en/waiting-list.html' => 'Waiting list',
        'en/my-wallet.html' => 'Wallet',
        'en/my-cards.html' => 'Means of payment',
        'en/my-account.html' => 'Identity and verification',
        'en/my-settings.html' => 'Preferences',
        'en/my-chat/subscriptions.html' => 'Chat',
    ];

    public function show(string $page): Response
    {
        if ($redirect = $this->adminRedirect($page)) {
            return $redirect;
        }

        return $this->pageResponse($page);
    }

    public function identityPage(): Response
    {
        if (Auth::user()?->isAdmin() && ! request()->is('en/admin*')) {
            return redirect('/en/admin/account/identity');
        }

        return $this->pageResponse('en/my-account.html', true);
    }

    protected function adminRedirect(string $page): ?Response
    {
        if (! Auth::user()?->isAdmin() || request()->is('en/admin*')) {
            return null;
        }

        $sections = [
            'en/dashboard.html' => 'dashboard',
            'en/my-shared-subscriptions.html' => 'shared-subscriptions',
            'en/joined-subscriptions.html' => 'joined-subscriptions',
            'en/waiting-list.html' => 'waiting-list',
            'en/my-wallet.html' => 'wallet',
            'en/my-cards.html' => 'cards',
            'en/my-account.html' => 'account',
            'en/my-settings.html' => 'settings',
            'en/my-chat/subscriptions.html' => 'chat',
        ];

        if (! isset($sections[$page])) {
            return null;
        }

        $section = $sections[$page];

        return redirect($section === 'dashboard' ? '/en/admin' : '/en/admin/'.$section);
    }

    public function authPage(string $type, Request $request): Response
    {
        $page = $type === 'register' ? 'en/register.html' : 'en/userlogin.html';
        $html = $this->readPage($page);

        $html = $this->replaceCsrfTokens($html);
        $html = str_replace(
            'id="logout-form" action="#offline"',
            'id="logout-form" action="/en/logout"',
            $html,
        );

        $return = $request->query('return');
        if (is_string($return) && str_starts_with($return, '/en/')) {
            $hiddenReturn = '<input type="hidden" name="return" value="'.e($return).'">';
            $html = preg_replace(
                '/(<form\b[^>]*\bid="(?:loginForm|registerForm)"[^>]*>)/i',
                '$1'.$hiddenReturn,
                $html,
                1,
            ) ?? $html;
        }

        $email = old('email');
        if ($email !== null) {
            $html = preg_replace(
                '/(name="email"[^>]*\bvalue=")[^"]*(")/i',
                '$1'.e($email).'$2',
                $html,
                1,
            ) ?? $html;
        }

        $errors = session('errors');
        if ($errors && $errors->any()) {
            $message = e($errors->first());
            $html = preg_replace(
                '/(<form\b[^>]*\bid="(?:loginForm|registerForm)"[^>]*>)/i',
                '$1<div class="alert alert-danger mb-3" role="alert">'.$message.'</div>',
                $html,
                1,
            ) ?? $html;
        }

        $html = $this->normalizePageLinks($html);

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function fallback(string $path): Response
    {
        $path = trim($path, '/');

        if (str_ends_with($path, '.html')) {
            return redirect('/'.substr($path, 0, -5), 301);
        }

        foreach ([$path, $path.'.html', $path.'/index.html'] as $candidate) {
            if (isset(self::PROTECTED_PAGES[$candidate]) && ! Auth::check()) {
                return redirect()->guest('/en/userlogin?return='.urlencode('/'.$path));
            }

            if ($this->pageExists($candidate)) {
                return $this->pageResponse($candidate);
            }
        }

        abort(404);
    }

    protected function pageResponse(string $page, bool $identityPage = false): Response
    {
        $html = $this->readPage($page);
        $html = $this->replaceCsrfTokens($html);
        $html = str_replace(
            'id="logout-form" action="#offline"',
            'id="logout-form" action="/en/logout"',
            $html,
        );

        $html = $this->applyAuthenticatedNavigation($html, $page);
        $html = $this->applyAuthenticatedUserData($html);
        $html = $this->applyAccountTabs($html, $identityPage);
        if ($identityPage) {
            $html = $this->applyIdentityPage($html);
        } elseif ($page === 'en/my-settings.html') {
            $html = $this->applySettingsData($html);
        } else {
            $html = $this->applyAccountData($html, $page);
        }

        $html = $this->applyDynamicContent($html, $page);

        $html = $this->normalizePageLinks($html);

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Arrow icon used inside catalogue and grid brand cards, copied from
     * the captured markup.
     */
    private const CATALOGUE_ARROW_SVG = '<svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 11.586C8.60954 11.9765 8.60953 12.6096 9 13C9.39046 13.3905 10.0235 13.3905 10.414 13L15.7069 7.70714C16.0974 7.31661 16.0974 6.68345 15.7069 6.29292L10.414 1.00003C10.0235 0.609564 9.39047 0.609564 9 1.00003C8.60953 1.39049 8.60954 2.02356 9 2.41403L12.586 6.00003H1C0.447715 6.00003 0 6.44774 0 7.00003C0 7.55231 0.447715 8.00003 1 8.00003H12.586L9 11.586Z" fill="currentColor"/></svg>';

    /** @var bool|null */
    protected $dynamicReady = null;

    /** @var array<string, string|null>|null */
    protected $dynamicSettings = null;

    /**
     * Render the brand detail page for any active brand, using the Amazon
     * capture as the template. Unknown or inactive slugs get a 404.
     * The coordinator wires GET /en/brand/{slug} to this method, before
     * the fallback route, so the existing /en/brand/amazon route keeps
     * working through show() and pageResponse().
     */
    public function showBrand(string $slug): Response
    {
        $brand = $this->findActiveBrand($slug);

        if ($brand === null) {
            abort(404);
        }

        $html = $this->readPage('en/brand/amazon.html');
        $html = $this->replaceCsrfTokens($html);
        $html = str_replace(
            'id="logout-form" action="#offline"',
            'id="logout-form" action="/en/logout"',
            $html,
        );
        $html = $this->applyAuthenticatedNavigation($html, 'en/brand/amazon.html');
        $html = $this->applyAuthenticatedUserData($html);
        $html = $this->applyBrandDetail($html, $brand);
        $html = $this->normalizePageLinks($html);

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Replace captured homepage, catalogue, house and brand detail markup
     * with database content. Only the four public pages listed here are
     * ever altered, so admin dashboard pages are untouched. Every section
     * keeps the captured HTML when the database has no active records for
     * it, and every setting keeps its captured piece when its key is
     * missing. A database error never breaks a public page: the captured
     * HTML is returned unchanged.
     */
    protected function applyDynamicContent(string $html, string $page): string
    {
        if (! in_array($page, ['en/index.html', 'en/subscriptions.html', 'en/subscriptions/house.html', 'en/brand/amazon.html'], true)) {
            return $html;
        }

        if (! $this->dynamicContentReady()) {
            return $html;
        }

        $original = $html;

        try {
            $html = $this->replaceHeroText($html);
            $html = $this->replaceSearchPlaceholder($html);
            $html = $this->replaceCategoryPills($html);
            $html = $this->replaceFeatures($html);
            $html = $this->replaceFooterLinks($html);

            if ($page === 'en/index.html') {
                $html = $this->replaceHeroCarousel($html);
                $html = $this->replacePressItems($html);
                $html = $this->replaceFaqs($html);
                $html = $this->replaceTestimonials($html);
                $html = $this->replaceShareSection($html);
                $html = $this->replaceFooterNote($html);
            } elseif ($page === 'en/subscriptions.html') {
                $html = $this->replaceCatalogueBlocks($html);
            } elseif ($page === 'en/subscriptions/house.html') {
                $html = $this->replaceHouseCards($html);
            } elseif ($page === 'en/brand/amazon.html') {
                $html = $this->applyBrandDetail($html, $this->findActiveBrand('amazon'));
            }
        } catch (\Throwable) {
            return $original;
        }

        return $html;
    }

    /**
     * True when every content model and table the frontend reads exists.
     * Lets the public pages keep serving the captured HTML before the
     * content migration has run.
     */
    protected function dynamicContentReady(): bool
    {
        if ($this->dynamicReady !== null) {
            return $this->dynamicReady;
        }

        $this->dynamicReady = false;

        try {
            foreach ([Brand::class, Category::class, SiteSetting::class, Faq::class, Testimonial::class, PressItem::class, FooterLinkGroup::class, Feature::class] as $model) {
                if (! class_exists($model)) {
                    return false;
                }
            }

            foreach (['brands', 'categories', 'site_settings', 'faqs', 'testimonials', 'press_items', 'footer_link_groups', 'footer_links', 'features'] as $table) {
                if (! Schema::hasTable($table)) {
                    return false;
                }
            }

            $this->dynamicReady = true;
        } catch (\Throwable) {
            $this->dynamicReady = false;
        }

        return $this->dynamicReady;
    }

    protected function findActiveBrand(string $slug): mixed
    {
        if (! $this->dynamicContentReady()) {
            return null;
        }

        try {
            return Brand::where('slug', $slug)->where('is_active', true)->first();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function dynamicSettings(): array
    {
        if ($this->dynamicSettings !== null) {
            return $this->dynamicSettings;
        }

        $keys = [
            'hero_highlight', 'hero_text', 'hero_underline', 'hero_search_placeholder',
            'share_subtitle', 'share_title_html', 'share_card_title', 'share_card_text',
            'share_card_button_label', 'subscribe_card_title', 'subscribe_card_text',
            'subscribe_card_button_label', 'share_content_html', 'footer_note',
            'features_title',
        ];

        try {
            $this->dynamicSettings = SiteSetting::whereIn('key', $keys)->pluck('value', 'key')->all();
        } catch (\Throwable) {
            $this->dynamicSettings = [];
        }

        return $this->dynamicSettings;
    }

    protected function dynamicSetting(string $key): ?string
    {
        $value = $this->dynamicSettings()[$key] ?? null;

        return $value === null || $value === '' ? null : (string) $value;
    }

    protected function activeCategories(): mixed
    {
        try {
            return Category::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    protected function allCategoriesByName(): array
    {
        try {
            $byName = [];
            foreach (Category::orderBy('sort_order')->orderBy('id')->get() as $category) {
                $byName[strtolower(trim((string) $category->name))] = $category;
            }

            return $byName;
        } catch (\Throwable) {
            return [];
        }
    }

    protected function featuredBrands(): mixed
    {
        try {
            return Brand::where('is_active', true)->where('is_featured', true)->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    protected function activeBrandsForCategory(int $categoryId): mixed
    {
        try {
            return Brand::where('is_active', true)->where('category_id', $categoryId)->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    protected function brandUrl(mixed $brand): string
    {
        return '/en/brand/'.(string) $brand->slug.'.html';
    }

    protected function formatBrandPrice(mixed $brand): string
    {
        $number = number_format((float) $brand->price, 2, '.', '');
        $number = rtrim(rtrim($number, '0'), '.');

        return (string) ($brand->currency_symbol ?: '').$number;
    }

    protected function brandBackground(mixed $brand): string
    {
        return (string) ($brand->background ?: '#112e45');
    }

    /**
     * Public storage URL for an uploaded file path. Paths that already
     * start with / or http are used as they are.
     */
    protected function storageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, '/') || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return '/storage/'.ltrim($path, '/');
    }

    /**
     * Brand logo or icon markup. logo_path wins over icon_svg. The icon
     * SVG is admin supplied inline SVG, rendered as stored, like the
     * captured markup does.
     */
    protected function brandIconHtml(mixed $brand): string
    {
        $logo = $this->storageUrl($brand->logo_path ?? null);

        if ($logo !== null) {
            return '<img src="'.e($logo).'" alt="'.e((string) $brand->name).'" loading="lazy">';
        }

        return (string) ($brand->icon_svg ?? '');
    }

    /**
     * The one shared brand card renderer, used by the homepage carousel,
     * the catalogue list and the house grid. Variants differ only in the
     * wrapping markup, which matches the capture for each place.
     */
    protected function renderBrandCard(mixed $brand, string $variant): string
    {
        $name = e((string) $brand->name);
        $url = e($this->brandUrl($brand));
        $price = e($this->formatBrandPrice($brand));

        if ($variant === 'catalogue') {
            return '<li class="category-card-item" linkable>'
                .'<a href="'.$url.'" class="category-card-item-name" linkable-target>'."\n".$name."\n".'</a>'
                .'<div class="category-card-item-price">'."\n".$price."\n".self::CATALOGUE_ARROW_SVG."\n".'</div>'
                .'</li>'."\n";
        }

        if ($variant === 'grid') {
            return '<div class="col-6 col-md-4 col-lg-3">'."\n"
                .'<div class="brand-card" linkable>'."\n"
                .'<div class="brand-card-title" style="background: '.e($this->brandBackground($brand)).'">'."\n"
                .'<a href="'.$url.'" linkable-target>'.$name.'</a>'."\n"
                .self::CATALOGUE_ARROW_SVG."\n"
                .'</div>'."\n"
                .'<div class="brand-card-wrapper">'."\n"
                .'<div class="brand-card-price">'.$price.'</div>'."\n"
                .'<div class="brand-card-frequency">/ month</div>'."\n"
                .'</div>'."\n"
                .'</div>'."\n"
                .'</div>'."\n";
        }

        $slide = '<div class="hero-slides-item splide__slide" style="background: '.e($this->brandBackground($brand)).'" linkable>'."\n"
            .'<div class="hero-slides-item-icon">'."\n"
            .$this->brandIconHtml($brand)."\n"
            .'</div>'."\n"
            .'<a href="'.$url.'" class="hero-slides-item-title" linkable-target>'.$name.'</a>'."\n"
            .'<hr class="hero-slides-item-separation">'."\n"
            .'<div class="hero-slides-item-price">'.$price.'</div>'."\n"
            .'<div class="hero-slides-item-frequency">/ month</div>'."\n";

        if ($brand->discount_percent !== null) {
            $slide .= '<div class="hero-slides-item-participation">'."\n"
                .'Participate in -'.e((string) $brand->discount_percent).' % <img src="/img/dashboard/cards/next.svg" alt="">'."\n"
                .'</div>'."\n";
        }

        return $slide.'</div>'."\n";
    }

    protected function replaceHeroText(string $html): string
    {
        $highlight = $this->dynamicSetting('hero_highlight');
        $text = $this->dynamicSetting('hero_text');
        $underline = $this->dynamicSetting('hero_underline');

        if ($highlight === null && $text === null && $underline === null) {
            return $html;
        }

        return preg_replace_callback(
            '/(<h1 class="hero-title">\s*<span>)([^<]*)(<\/span>)([^<]*)(<u>)([^<]*)(<\/u>)/s',
            static function (array $matches) use ($highlight, $text, $underline): string {
                $middle = $text !== null ? ' '.e($text).' ' : $matches[4];

                return $matches[1].e($highlight ?? $matches[2]).$matches[3].$middle.e($underline ?? $matches[6]).$matches[7];
            },
            $html,
            1,
        ) ?? $html;
    }

    /**
     * hero_search_placeholder has no placeholder attribute in the capture:
     * the hero-search component renders the input. The value is applied
     * as a placeholder attribute on the component tag, and kept out when
     * the key is missing.
     */
    protected function replaceSearchPlaceholder(string $html): string
    {
        $placeholder = $this->dynamicSetting('hero_search_placeholder');

        if ($placeholder === null) {
            return $html;
        }

        return preg_replace_callback(
            '/<hero-search\b[^>]*>/s',
            static function (array $matches) use ($placeholder): string {
                $tag = preg_replace('/\splaceholder="[^"]*"/i', '', $matches[0]) ?? $matches[0];

                return substr($tag, 0, -1).' placeholder="'.e($placeholder).'">';
            },
            $html,
        ) ?? $html;
    }

    protected function renderCategoryPill(mixed $category): string
    {
        $href = $category->slug === 'house' ? '/en/subscriptions/house.html' : '#offline';
        $pill = '<span class="hero-categories-item splide__slide" linkable>'."\n";

        if (! empty($category->icon_svg)) {
            $pill .= '<span>'."\n".(string) $category->icon_svg."\n".'</span>'."\n";
        }

        return $pill.'<a href="'.$href.'" linkable-target>'."\n".e((string) $category->name)."\n".'</a>'."\n".'</span>'."\n";
    }

    protected function replaceCategoryPills(string $html): string
    {
        if (! str_contains($html, 'hero-categories-items')) {
            return $html;
        }

        $categories = $this->activeCategories();

        if ($categories->isEmpty()) {
            return $html;
        }

        $pills = '';
        foreach ($categories as $category) {
            $pills .= $this->renderCategoryPill($category);
        }

        return preg_replace_callback(
            '/(<div class="splide__list hero-categories-items">)(.*?)(<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<div class="hero-search-wrapper">)/s',
            static fn (array $matches): string => $matches[1]."\n".$pills.$matches[3],
            $html,
            1,
        ) ?? $html;
    }

    protected function replaceHeroCarousel(string $html): string
    {
        $brands = $this->featuredBrands();

        if ($brands->isEmpty()) {
            return $html;
        }

        $slides = '';
        foreach ($brands as $brand) {
            $slides .= $this->renderBrandCard($brand, 'hero');
        }

        return preg_replace_callback(
            '/(<div class="splide__list hero-slides">)(.*?)(<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<div class="container">)/s',
            static fn (array $matches): string => $matches[1]."\n".$slides.$matches[3],
            $html,
            1,
        ) ?? $html;
    }

    protected function replacePressItems(string $html): string
    {
        if (! str_contains($html, 'press-items')) {
            return $html;
        }

        try {
            $items = PressItem::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return $html;
        }

        if ($items->isEmpty()) {
            return $html;
        }

        $slides = '';
        foreach ($items as $item) {
            $href = e((string) ($item->link_url ?: '#'));
            $logo = $this->storageUrl($item->logo_path ?? null);
            if ($logo !== null) {
                $inner = '<img src="'.e($logo).'" loading="lazy" alt="'.e((string) $item->outlet_name).'" />';
            } elseif (! empty($item->logo_text)) {
                $inner = e((string) $item->logo_text);
            } else {
                $inner = e((string) $item->outlet_name);
            }
            $slides .= '<div class="press-item splide__slide">'."\n"
                .'<a href="'.$href.'" class="col-6 col-md-3" target="_blank">'.$inner.'</a>'."\n"
                .'</div>'."\n";
        }

        return preg_replace_callback(
            '/(<div class="splide__list press-items">)(.*?)(<!-- \/Médias -->)/s',
            static fn (array $matches): string => $matches[1]."\n".$slides.$matches[3],
            $html,
            1,
        ) ?? $html;
    }

    protected function replaceFeatures(string $html): string
    {
        if (! str_contains($html, 'feature-section-items')) {
            return $html;
        }

        try {
            $features = Feature::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return $html;
        }

        if ($features->isEmpty()) {
            return $html;
        }

        $title = $this->dynamicSetting('features_title');
        if ($title !== null) {
            $html = preg_replace_callback(
                '/(<section class="feature-section" id="features-section">\s*<div class="container">\s*<h3>)[^<]*(<\/h3>)/s',
                static fn (array $matches): string => $matches[1].e($title).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $items = '';
        foreach ($features as $index => $feature) {
            $color = $index % 2 === 0 ? 'blue' : 'green';
            $icon = '';
            if (! empty($feature->icon_svg)) {
                $icon = (string) $feature->icon_svg;
            } elseif (($iconPath = $this->storageUrl($feature->icon_path ?? null)) !== null) {
                $icon = '<img src="'.e($iconPath).'" loading="lazy">';
            }
            $link = (string) $feature->title === 'Buyer Protection Program' ? '/cms/en/buyer-protection-program/' : '#offline';
            $items .= '<div class="feature-section-item col-12 col-md-6 col-lg-3">'."\n"
                .'<div class="img-wrapper">'."\n".$icon."\n".'</div>'."\n"
                .'<div class="title">'.e((string) $feature->title).'</div>'."\n"
                .'<p class="content">'.nl2br(e((string) ($feature->text ?? ''))).'</p>'."\n"
                .'<a href="'.$link.'" class="link text-'.$color.'">'."\n"
                .'<span>Read More</span>'."\n"
                .'<img src="/img/dashboard/features/next-'.$color.'.svg" alt="">'."\n"
                .'</a>'."\n"
                .'</div>'."\n";
        }

        return preg_replace_callback(
            '/(<div class="feature-section-items row">)(.*?)(<\/div>\s*<\/div>\s*<\/div>\s*<\/section>)/s',
            static fn (array $matches): string => $matches[1]."\n".$items.$matches[3],
            $html,
            1,
        ) ?? $html;
    }

    protected function replaceFaqs(string $html): string
    {
        if (! str_contains($html, 'faq-items')) {
            return $html;
        }

        try {
            $faqs = Faq::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return $html;
        }

        if ($faqs->isEmpty()) {
            return $html;
        }

        $items = '';
        foreach ($faqs as $faq) {
            $items .= '<section class="faq-item active" toggle>'."\n"
                .'<h4 class="faq-item-title" toggle-target>'."\n"
                .e((string) $faq->question).' </h4>'."\n"
                .'<div class="faq-item-content">'."\n"
                .'<div class="p2-regular">'.nl2br(e((string) $faq->answer)).'</div>'."\n"
                .'</div>'."\n"
                .'</section>'."\n";
        }

        return preg_replace_callback(
            '/(<div class="faq-items">)(.*?)(<\/div>\s*<\/div>\s*<\/section>\s*<section class="testimonials-section")/s',
            static fn (array $matches): string => $matches[1]."\n".$items.$matches[3],
            $html,
            1,
        ) ?? $html;
    }

    protected function replaceTestimonials(string $html): string
    {
        if (! str_contains($html, 'testimonials-items')) {
            return $html;
        }

        try {
            $testimonials = Testimonial::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return $html;
        }

        if ($testimonials->isEmpty()) {
            return $html;
        }

        $slides = '';
        foreach ($testimonials as $testimonial) {
            $avatar = $this->storageUrl($testimonial->avatar_path ?? null) ?? '/img/dashboard/testimonials/avatar-1.png?v=0388437';
            $rating = $testimonial->rating !== null ? max(1, min(5, (int) $testimonial->rating)) : 5;
            $stars = '';
            for ($i = 0; $i < $rating; $i++) {
                $stars .= '<img src="/img/dashboard/testimonials/full-star.svg" alt="Etoile complete" loading="lazy">'."\n";
            }
            $slides .= '<div class="testimonials-item splide__slide">'."\n"
                .'<div class="testimonials-item-header">'."\n"
                .'<img src="'.e($avatar).'" class="testimonials-item-avatar" loading="lazy">'."\n"
                .'<div class="testimonials-item-user">'.e((string) $testimonial->author_name).'</div>'."\n"
                .'</div>'."\n"
                .'<p class="testimonials-item-content">'.e((string) $testimonial->quote).'</p>'."\n"
                .'<div class="testimonials-item-footer">'."\n"
                .'<div>'."\n".$stars.'</div>'."\n"
                .'<div>'.e((string) ($testimonial->role_text ?: 'since Google')).'</div>'."\n"
                .'</div>'."\n"
                .'</div>'."\n";
        }

        return preg_replace_callback(
            '/(<div class="splide__list testimonials-items">)(.*?)(<\/div>\s*<\/div>\s*<\/div>\s*<\/section>)/s',
            static fn (array $matches): string => $matches[1]."\n".$slides.$matches[3],
            $html,
            1,
        ) ?? $html;
    }

    protected function replaceShareSection(string $html): string
    {
        if (! str_contains($html, 'share-section')) {
            return $html;
        }

        $subtitle = $this->dynamicSetting('share_subtitle');
        if ($subtitle !== null) {
            $html = preg_replace_callback(
                '/(<div class="share-section-subtitle">)[^<]*(<\/div>)/',
                static fn (array $matches): string => $matches[1].e($subtitle).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $titleHtml = $this->dynamicSetting('share_title_html');
        if ($titleHtml !== null) {
            $html = preg_replace_callback(
                '/(<h3 class="share-section-title">).*?(<\/h3>)/s',
                static fn (array $matches): string => $matches[1].$titleHtml.$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $html = $this->replaceShareCard($html, 'green', $this->dynamicSetting('share_card_title'), $this->dynamicSetting('share_card_text'), $this->dynamicSetting('share_card_button_label'));
        $html = $this->replaceShareCard($html, 'blue', $this->dynamicSetting('subscribe_card_title'), $this->dynamicSetting('subscribe_card_text'), $this->dynamicSetting('subscribe_card_button_label'));

        $contentHtml = $this->dynamicSetting('share_content_html');
        if ($contentHtml !== null) {
            $html = preg_replace_callback(
                '/(<div class="share-content col-12 col-lg-4 offset-xl-1">).*?(<\/div>\s*<\/div>\s*<\/div>\s*<\/section>)/s',
                static fn (array $matches): string => $matches[1].$contentHtml.$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }

    protected function replaceShareCard(string $html, string $color, ?string $title, ?string $text, ?string $buttonLabel): string
    {
        if ($title === null && $text === null && $buttonLabel === null) {
            return $html;
        }

        $card = preg_quote($color, '/');

        if ($title !== null) {
            $html = preg_replace_callback(
                '/(<div class="share-card '.$card.'">.*?<h2 class="title">)[^<]*(<\/h2>)/s',
                static fn (array $matches): string => $matches[1].e($title).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        if ($text !== null) {
            $html = preg_replace_callback(
                '/(<div class="share-card '.$card.'">.*?<p class="content">)[^<]*(<\/p>)/s',
                static fn (array $matches): string => $matches[1].e($text).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        if ($buttonLabel !== null) {
            $html = preg_replace_callback(
                '/(<div class="share-card '.$card.'">.*?<a [^>]*class="btn-sharesub dark big">)[^<]*(<\/a>)/s',
                static fn (array $matches): string => $matches[1].e($buttonLabel).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }

    protected function replaceFooterNote(string $html): string
    {
        $note = $this->dynamicSetting('footer_note');

        if ($note === null) {
            return $html;
        }

        return preg_replace_callback(
            '/(<p class="hero-brand-disclaimer">).*?(<\/p>)/s',
            static fn (array $matches): string => $matches[1].e($note).$matches[2],
            $html,
            1,
        ) ?? $html;
    }

    /**
     * Footer link columns. The first two groups render in the middle
     * column, each as a col-lg-6 block like the capture; any further
     * groups render in the right hand column, like the captured Hot
     * topics column. Groups with no active links still render their
     * title, like Hot topics does in the capture.
     */
    protected function replaceFooterLinks(string $html): string
    {
        if (! str_contains($html, 'footer-links')) {
            return $html;
        }

        try {
            $groups = FooterLinkGroup::with(['footerLinks' => function ($query): void {
                $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
            }])->orderBy('sort_order')->orderBy('id')->get();
        } catch (\Throwable) {
            return $html;
        }

        if ($groups->isEmpty()) {
            return $html;
        }

        $renderGroup = static function (mixed $group, bool $wrap): string {
            $out = $wrap ? '<div class="col-12 col-lg-6 my-3">' : '';
            $out .= '<div class="footer-link-title mb-4">'.e((string) $group->title).'</div>';
            foreach ($group->footerLinks as $link) {
                $out .= '<div class="footer-link mb-3"><a class="text-decoration-none" href="'.e((string) $link->url).'">'.e((string) $link->label).'</a></div>';
            }

            return $wrap ? $out.'</div>' : $out;
        };

        $middle = '';
        $right = '';
        foreach ($groups as $index => $group) {
            if ($index < 2) {
                $middle .= $renderGroup($group, true);
            } else {
                $right .= $renderGroup($group, false);
            }
        }

        // One anchored pass from the middle column to the end of the
        // footer links row. The first column (Made with...) shares the
        // right hand column class, so the right column must not be
        // matched on its own or the whole row gets wiped.
        $rightColumn = '<div class="col-12 col-md-4 col-lg-3 my-3">'."\n".$right;

        return preg_replace_callback(
            '/(<div class="col-12 col-md-4 col-lg-6">\s*<div class="row">)(.*?)(<\/div>\s*<\/div>\s*<\/section>)/s',
            static fn (array $matches): string => $matches[1]."\n".$middle."</div>\n                    </div>\n                    ".$rightColumn.$matches[3],
            $html,
            1,
        ) ?? $html;
    }

    protected function replaceCatalogueBlocks(string $html): string
    {
        if (! str_contains($html, 'category-cards')) {
            return $html;
        }

        $byName = $this->allCategoriesByName();

        if ($byName === []) {
            return $html;
        }

        $seen = [];
        $html = preg_replace_callback(
            '/<div class="category-card">\s*<h2 class="category-card-title">\s*(<svg.*?<\/svg>)?\s*<span>([^<]+)<\/span>\s*<\/h2>\s*<div class="category-card-wrapper">\s*<p class="category-card-description">.*?<\/p>\s*<ul class="category-card-items">.*?<\/ul>\s*<a href="[^"]*" class="btn btn-sharesub blue">See all<\/a>\s*<\/div>\s*<\/div>/s',
            function (array $matches) use ($byName, &$seen): string {
                $name = html_entity_decode(trim($matches[2]), ENT_QUOTES, 'UTF-8');
                $key = strtolower($name);
                $seen[$key] = true;

                $category = $byName[$key] ?? null;
                if ($category === null) {
                    return $matches[0];
                }

                if (! $category->is_active) {
                    return '';
                }

                $brands = $this->activeBrandsForCategory((int) $category->id);
                if ($brands->isEmpty()) {
                    return $matches[0];
                }

                $block = $matches[0];

                if (! empty($category->icon_svg)) {
                    if ($matches[1] !== '') {
                        $block = str_replace($matches[1], (string) $category->icon_svg, $block);
                    } else {
                        $icon = (string) $category->icon_svg;
                        $block = preg_replace_callback(
                            '/(<h2 class="category-card-title">\s*)/',
                            static fn (array $inner): string => $inner[1].$icon,
                            $block,
                            1,
                        ) ?? $block;
                    }
                }

                $items = '';
                foreach ($brands as $brand) {
                    $items .= $this->renderBrandCard($brand, 'catalogue');
                }

                return preg_replace_callback(
                    '/<ul class="category-card-items">.*?<\/ul>/s',
                    static fn (array $inner): string => '<ul class="category-card-items">'."\n".$items.'</ul>',
                    $block,
                    1,
                ) ?? $block;
            },
            $html,
        ) ?? $html;

        $extra = '';
        foreach ($byName as $key => $category) {
            if (isset($seen[$key]) || ! $category->is_active) {
                continue;
            }

            $brands = $this->activeBrandsForCategory((int) $category->id);
            if ($brands->isEmpty()) {
                continue;
            }

            $items = '';
            foreach ($brands as $brand) {
                $items .= $this->renderBrandCard($brand, 'catalogue');
            }

            $seeAll = $category->slug === 'house' ? '/en/subscriptions/house.html' : '#offline';
            $extra .= '<div class="category-card">'."\n"
                .'<h2 class="category-card-title">'."\n"
                .((string) ($category->icon_svg ?? ''))."\n"
                .'<span>'.e((string) $category->name).'</span>'."\n"
                .'</h2>'."\n"
                .'<div class="category-card-wrapper">'."\n"
                .'<ul class="category-card-items">'."\n".$items.'</ul>'."\n"
                .'<a href="'.$seeAll.'" class="btn btn-sharesub blue">See all</a>'."\n"
                .'</div>'."\n"
                .'</div>'."\n";
        }

        if ($extra === '') {
            return $html;
        }

        return preg_replace_callback(
            '/(<\/div>\s*<div class="category-actions">)/',
            static fn (array $matches): string => $extra.$matches[1],
            $html,
            1,
        ) ?? $html;
    }

    protected function replaceHouseCards(string $html): string
    {
        if (! str_contains($html, 'brand-cards')) {
            return $html;
        }

        try {
            $category = Category::where('slug', 'house')->where('is_active', true)->first();
        } catch (\Throwable) {
            return $html;
        }

        if ($category === null) {
            return $html;
        }

        $brands = $this->activeBrandsForCategory((int) $category->id);

        if ($brands->isEmpty()) {
            return $html;
        }

        $html = preg_replace_callback(
            '/(<h1 class="brand-header-title col-12"><span>Subscription sharing<\/span>)\s*[^<]+(<\/h1>)/',
            static fn (array $matches): string => $matches[1].' '.e((string) $category->name).$matches[2],
            $html,
            1,
        ) ?? $html;

        if (! empty($category->icon_svg)) {
            $icon = (string) $category->icon_svg;
            $html = preg_replace_callback(
                '/(<h2 class="brand-content-title">\s*)<svg.*?<\/svg>/s',
                static fn (array $matches): string => $matches[1].$icon,
                $html,
                1,
            ) ?? $html;
        }

        $html = preg_replace_callback(
            '/(<h2 class="brand-content-title">.*?<\/svg>)\s*[^<]+(<\/h2>)/s',
            static fn (array $matches): string => $matches[1].' '.e((string) $category->name).$matches[2],
            $html,
            1,
        ) ?? $html;

        $cards = '';
        foreach ($brands as $brand) {
            $cards .= $this->renderBrandCard($brand, 'grid');
        }

        return preg_replace_callback(
            '/(<div class="row brand-cards">)(.*?)(<div class="category-actions">)/s',
            static fn (array $matches): string => $matches[1]."\n".$cards.$matches[3],
            $html,
            1,
        ) ?? $html;
    }

    /**
     * Substitute one brand into the Amazon detail template. Only the
     * header, title and meta description are rewritten. The group offer
     * rows further down the page stay exactly as captured, because they
     * depend on live user offers, not on the brand record. The create
     * offer price in the header also stays as captured for the same
     * reason; only the join price comes from the brand record.
     */
    protected function applyBrandDetail(string $html, mixed $brand): string
    {
        if ($brand === null) {
            return $html;
        }

        $name = (string) $brand->name;
        $price = $this->formatBrandPrice($brand);
        $category = null;

        try {
            $category = $brand->category;
        } catch (\Throwable) {
            $category = null;
        }

        $html = preg_replace_callback(
            '/(<title>)([^<]*)(<\/title>)/s',
            static fn (array $matches): string => $matches[1].'Share my '.e($name).' subscription on Sharesub'.$matches[3],
            $html,
            1,
        ) ?? $html;

        $html = preg_replace_callback(
            '/(<meta name="description" content=")([^"]*)(")/',
            static function (array $matches) use ($name, $price): string {
                $content = str_replace('Amazon', $name, $matches[2]);
                $content = str_replace('£1.85', $price, $content);

                return $matches[1].e($content).$matches[3];
            },
            $html,
            1,
        ) ?? $html;

        if ($category !== null) {
            $html = preg_replace_callback(
                '/(<div class="model-header-breadcrumb">\s*<a href="#offline">)[^<]*(<\/a>)/s',
                static fn (array $matches): string => $matches[1].e((string) $category->name).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $html = preg_replace_callback(
            '/(<div class="model-header-breadcrumb">.*?<span>&gt;<\/span>\s*<span>)[^<]*(<\/span>)/s',
            static fn (array $matches): string => $matches[1].e($name).$matches[2],
            $html,
            1,
        ) ?? $html;

        if (! empty($brand->background)) {
            $html = str_replace(
                '<div class="model-header">',
                '<div class="model-header" style="background: '.e((string) $brand->background).'">',
                $html,
            );
        }

        $icon = $this->brandIconHtml($brand);
        $html = preg_replace_callback(
            '/(<span class="model-header-name-brand">)\s*[^<]*(<\/span>)/',
            static function (array $matches) use ($name, $icon): string {
                $inner = $icon !== '' ? $icon.' ' : '';

                return $matches[1].' '.$inner.e($name).$matches[2];
            },
            $html,
            1,
        ) ?? $html;

        if ($brand->discount_percent !== null) {
            $html = preg_replace_callback(
                '/(<span>Save up to <strong>)[^<]*(<\/strong><\/span>)/',
                static fn (array $matches): string => $matches[1].e((string) $brand->discount_percent).'%'.$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $lead = $brand->short_description ?? $brand->tagline;
        if ($lead !== null && $lead !== '') {
            $html = preg_replace_callback(
                '/(<div class="model-header-lead" data-more="See more">).*?(<\/div>)/s',
                static fn (array $matches): string => $matches[1].e((string) $lead).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $html = preg_replace_callback(
            '/(<div class="model-header-offer-card model-header-offer-card--join">.*?<strong>)([^<]*)(<small>\/ month<\/small><\/strong>)/s',
            static fn (array $matches): string => $matches[1].e($price).' '.$matches[3],
            $html,
            1,
        ) ?? $html;

        $html = str_replace('Amazon seats available', e($name).' seats available', $html);
        $html = str_replace('Before joining a group Amazon', 'Before joining a group '.e($name), $html);
        $html = str_replace('Blog posts about Amazon', 'Blog posts about '.e($name), $html);
        $html = str_replace('invitation link Amazon', 'invitation link '.e($name), $html);

        return $html;
    }

    protected function applyAccountTabs(string $html, bool $identityPage): string
    {
        $accountClass = 'col-6 col-md-4 p-2 tab-navigation'.($identityPage ? '' : ' current');
        $identityClass = 'col-6 col-md-4 p-2 tab-navigation'.($identityPage ? ' current' : '');

        $html = preg_replace(
            '/<a href="\/en\/my-account\.html" class="col-6 col-md-4 p-2 tab-navigation">/',
            '<a href="/en/my-account/identity" class="'.$identityClass.'">',
            $html,
            1,
        ) ?? $html;
        $html = preg_replace(
            '/<a href="\/en\/dashboard\.html" class="col-6 col-md-4 p-2 tab-navigation current">/',
            '<a href="/en/my-account.html" class="'.$accountClass.'">',
            $html,
            1,
        ) ?? $html;
        $html = str_replace(
            'href="/en/my-account.html">Identity &amp; Verification',
            'href="/en/my-account/identity">Identity &amp; Verification',
            $html,
        );
        $html = str_replace(
            'href="/en/my-account.html" class="navi_dash-link active "',
            'href="/en/my-account/identity" class="navi_dash-link active "',
            $html,
        );
        $html = str_replace(
            'href="/en/my-account.html" class="dash-sheet-link active"',
            'href="/en/my-account/identity" class="dash-sheet-link active"',
            $html,
        );

        return $html;
    }

    protected function applyIdentityPage(string $html): string
    {
        $submission = Auth::user()->kycSubmission;
        $html = $this->applyKycStatusNotice($html);
        // Keep validation errors visible, but do not duplicate the template's
        // identity notice with a second success flash after submission.
        $message = session('errors')?->first('kyc') ?: session('errors')?->first();

        $status = $submission?->status ?? KycSubmission::STATUS_NOT_SUBMITTED;
        $form = $this->identityForm($submission, $message);
        $html = preg_replace(
            '/<form\b[^>]*\bclass="stripe-create"[^>]*>.*?<\/form>/is',
            $form,
            $html,
            1,
        ) ?? $html;

        return $html;
    }

    protected function applyKycStatusNotice(string $html): string
    {
        $submission = Auth::user()->kycSubmission;
        $status = $submission?->status ?? KycSubmission::STATUS_NOT_SUBMITTED;
        $toast = match ($status) {
            KycSubmission::STATUS_UNDER_REVIEW => 'Your identity documents are under review.',
            KycSubmission::STATUS_APPROVED => 'Your identity has been approved.',
            KycSubmission::STATUS_REJECTED => 'Your identity verification was rejected. Please submit new documents.',
            default => 'Please complete your identity verification.',
        };
        if ($submission?->admin_note && $status === KycSubmission::STATUS_REJECTED) {
            $toast .= ' Reason: '.e($submission->admin_note);
        }

        $html = str_replace(
            'Please complete your identity first (my account information form)',
            $toast,
            $html,
        );
        if ($status === KycSubmission::STATUS_APPROVED) {
            $html = preg_replace(
                '/(<div class="sharesub-flash sharesub-flash--warning" role="alert")/',
                '$1 style="display:none"',
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }

    protected function identityForm(?KycSubmission $submission, ?string $message): string
    {
        $status = $submission?->status ?? KycSubmission::STATUS_NOT_SUBMITTED;
        $statusLabel = match ($status) {
            KycSubmission::STATUS_UNDER_REVIEW => 'Under review',
            KycSubmission::STATUS_APPROVED => 'Approved',
            KycSubmission::STATUS_REJECTED => 'Rejected',
            default => 'Not submitted',
        };
        $statusClass = $status === KycSubmission::STATUS_APPROVED ? 'alert-success' : ($status === KycSubmission::STATUS_REJECTED ? 'alert-danger' : 'alert-info');
        $html = '<div class="p1-bold mb-2">Identity verification</div>'
            .'<div class="alert '.$statusClass.' mb-3" role="status"><strong>Status: '.e($statusLabel).'</strong>';
        if ($status === KycSubmission::STATUS_UNDER_REVIEW) {
            $html .= '<br>Your CNIC documents have been submitted. An administrator will review them.';
        } elseif ($status === KycSubmission::STATUS_APPROVED) {
            $html .= '<br>Your identity has been verified successfully.';
        } elseif ($status === KycSubmission::STATUS_REJECTED && $submission?->admin_note) {
            $html .= '<br>'.e($submission->admin_note);
        }
        $html .= '</div>';

        if ($message) {
            $html .= '<div class="alert alert-danger mb-3" role="alert">'.e($message).'</div>';
        }

        if (in_array($status, [KycSubmission::STATUS_APPROVED, KycSubmission::STATUS_UNDER_REVIEW], true)) {
            return '<div class="kyc-upload-panel">'.$html.'</div>';
        }

        return '<form class="kyc-upload-form" method="POST" action="/en/my-account/identity" enctype="multipart/form-data">'
            .'<input type="hidden" name="_token" value="'.csrf_token().'">'
            .$html
            .'<p class="mb-3">Upload the front and back of your CNIC. Accepted files: JPG, PNG or PDF, maximum 5 MB each.</p>'
            .'<div class="form-group"><label for="cnic_front">CNIC front</label><input type="file" name="cnic_front" id="cnic_front" class="input-sharesub" accept="image/jpeg,image/png,application/pdf" required></div>'
            .'<div class="form-group"><label for="cnic_back">CNIC back</label><input type="file" name="cnic_back" id="cnic_back" class="input-sharesub" accept="image/jpeg,image/png,application/pdf" required></div>'
            .'<button type="submit" class="btn-sharesub bleu mx-auto my-2 d-block">Submit for review</button>'
            .'</form>';
    }

    protected function applySettingsData(string $html): string
    {
        $user = Auth::user();

        $html = str_replace(
            '<form method="POST" action="#security">',
            '<form method="POST" action="/en/my-settings/security">',
            $html,
        );
        $html = str_replace(
            '<form method="POST" action="#settings">',
            '<form method="POST" action="/en/my-settings/preferences">',
            $html,
        );

        $html = preg_replace_callback(
            '/(<input\b(?=[^>]*\bname="referral_code")[^>]*\bvalue=")[^"]*(")/i',
            fn (array $match): string => $match[1].e((string) ($user->referral_code ?? '')).$match[2],
            $html,
            1,
        ) ?? $html;

        $language = (string) ($user->language ?: 'en');
        $html = preg_replace_callback(
            '/(<select\b[^>]*\bname="lang"[^>]*>.*?<\/select>)/is',
            function (array $matches) use ($language): string {
                $select = preg_replace('/\sselected\b/i', '', $matches[1]) ?? $matches[1];
                $value = preg_quote($language, '/');

                return preg_replace(
                    '/(<option\b[^>]*\bvalue="'.$value.'"[^>]*)>/i',
                    '$1 selected>',
                    $select,
                    1,
                ) ?? $select;
            },
            $html,
            1,
        ) ?? $html;

        $html = $this->setSettingsCheckbox($html, 'payoutLowAmount', (bool) $user->payout_low_amount);
        $html = $this->setSettingsCheckbox($html, 'payoutConvertCurrency', (bool) $user->payout_convert_currency);

        $devices = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get();
        $deviceMarkup = $this->trustedDevicesMarkup($devices, session()->getId());
        $html = str_replace(
            '<p class="text-muted">No device registered.</p>',
            $deviceMarkup,
            $html,
        );

        $deleteForm = '<form method="POST" action="/en/my-settings/delete" class="settings-delete-form">'
            .'<input type="hidden" name="_token" value="'.csrf_token().'">'
            .'<div class="p1-bold mt-4 mb-2">Account deletion</div>'
            .'<p>To delete your account, you must have cancelled all subscriptions and stopped all sharing. Deletion is definitive and entails loss of access to your data and the impossibility of obtaining refunds or transfers</p>'
            .'<p>To permanently delete your account, type "Delete" and confirm:</p>'
            .'<div class="form-row align-items-center">'
            .'<div class="col"><input type="text" name="confirmation" class="input-sharesub" placeholder="Delete" required></div>'
            .'<div class="col-auto"><button type="submit" class="btn-sharesub red outline delete-account-button">Delete my account</button></div>'
            .'</div>'
            .'</form>';
        $html = preg_replace(
            '/<delete-account\b.*?<\/delete-account>/is',
            $deleteForm,
            $html,
            1,
        ) ?? $html;

        $errors = session('errors');
        $flash = '';
        if ($errors && $errors->any()) {
            $flash = '<div class="alert alert-danger mb-3" role="alert">'.e($errors->first()).'</div>';
        } elseif (is_string(session('status'))) {
            $flash = '<div class="alert alert-success mb-3" role="status">'.e(session('status')).'</div>';
        }

        if ($flash !== '') {
            $tab = (string) session('settings_tab', 'security');
            $action = match ($tab) {
                'preferences' => '/en/my-settings/preferences',
                'delete' => '/en/my-settings/delete',
                default => '/en/my-settings/security',
            };
            $html = preg_replace(
                '/(<form\b[^>]*\baction="'.preg_quote($action, '/').'"[^>]*>)/i',
                '$1'.$flash,
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }

    protected function setSettingsCheckbox(string $html, string $id, bool $checked): string
    {
        return preg_replace_callback(
            '/<input\b[^>]*\bid="'.preg_quote($id, '/').'"[^>]*>/i',
            static function (array $matches) use ($checked): string {
                $tag = preg_replace('/\schecked\b/i', '', $matches[0]) ?? $matches[0];
                if (! $checked) {
                    return $tag;
                }

                return preg_replace('/\s*\/>$/', ' checked />', $tag)
                    ?? preg_replace('/>$/', ' checked>', $tag)
                    ?? $tag;
            },
            $html,
            1,
        ) ?? $html;
    }

    protected function trustedDevicesMarkup(object $devices, string $currentSessionId): string
    {
        if ($devices->isEmpty()) {
            return '<p class="text-muted">No device registered.</p>';
        }

        $html = '<div class="settings-trusted-devices">';
        foreach ($devices as $device) {
            $isCurrent = (string) $device->id === $currentSessionId;
            $lastActive = date('Y-m-d H:i', (int) $device->last_activity);
            $label = $isCurrent ? 'This device' : 'Signed-in device';
            $details = $label.' · '.((string) ($device->ip_address ?: 'Unknown IP')).' · Last active '.$lastActive;
            $html .= '<div class="d-flex align-items-center justify-content-between mb-2">'
                .'<span class="text-muted">'.e($details).'</span>';
            if (! $isCurrent) {
                $html .= '<form method="POST" action="/en/my-settings/device/revoke" class="ml-2">'
                    .'<input type="hidden" name="_token" value="'.csrf_token().'">'
                    .'<input type="hidden" name="session_id" value="'.e((string) $device->id).'">'
                    .'<button type="submit" class="btn-sharesub red outline small">Revoke</button>'
                    .'</form>';
            }
            $html .= '</div>';
        }

        return $html.'</div>';
    }

    protected function applyAuthenticatedUserData(string $html): string
    {
        if (! Auth::check()) {
            return $html;
        }

        $user = Auth::user();
        $nameParts = preg_split('/\s+/', trim((string) $user->name), 2) ?: [];
        $firstName = (string) ($user->first_name ?: ($nameParts[0] ?? ''));
        $fullName = trim((string) ($user->first_name ?: '').' '.(string) ($user->last_name ?: ''));
        $fullName = $fullName !== '' ? $fullName : (string) $user->name;
        $publicName = (string) ($user->username ?: $fullName ?: Str::before((string) $user->email, '@'));

        $javascriptValues = [
            'email' => (string) $user->email,
            'fullname' => $publicName,
        ];

        foreach ($javascriptValues as $key => $value) {
            $escapedKey = preg_quote($key, '/');
            $encodedValue = json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            $html = preg_replace_callback(
                '/(window\.sharesubVars\[\''.$escapedKey.'\'\]\s*=\s*)\'[^\']*\'/i',
                static fn (array $match): string => $match[1].$encodedValue,
                $html,
            ) ?? $html;
        }

        if ($firstName !== '') {
            $html = preg_replace_callback(
                '/(<h1\b[^>]*\bclass="dashboard-header-title"[^>]*>\s*Hello,\s*<span\b[^>]*\bclass="text-green"[^>]*>)[^<]*(<\/span>\s*<\/h1>)/i',
                static fn (array $match): string => $match[1].e($firstName).$match[2],
                $html,
            ) ?? $html;
        }

        return $html;
    }

    protected function applyAccountData(string $html, string $page): string
    {
        if ($page !== 'en/my-account.html' || ! Auth::check()) {
            return $html;
        }

        $html = $this->applyKycStatusNotice($html);
        $user = Auth::user();
        $nameParts = preg_split('/\s+/', trim((string) $user->name), 2) ?: [];
        $values = [
            'firstname' => $user->first_name ?: ($nameParts[0] ?? ''),
            'lastname' => $user->last_name ?: ($nameParts[1] ?? ''),
            'username' => $user->username,
            'birthdate' => $user->birthdate?->format('Y-m-d') ?? $user->birthdate,
            'email' => $user->email,
            'phone' => $user->phone,
            'intl-phone' => $user->intl_phone,
            'address1' => $user->address1,
            'address2' => $user->address2,
            'city' => $user->city,
            'zip' => $user->zip,
            'state' => $user->state,
        ];

        $html = preg_replace(
            '/(<form\b[^>]*\bclass="stripe-create"[^>]*)(>)/i',
            '$1 action="/en/my-account.html"$2',
            $html,
            1,
        ) ?? $html;

        foreach ($values as $field => $value) {
            $escapedField = preg_quote($field, '/');
            $escapedValue = e((string) ($value ?? ''));
            $html = preg_replace(
                '/(<input\b(?=[^>]*\bname="'.$escapedField.'")[^>]*\bvalue=")[^"]*(")/i',
                '${1}'.$escapedValue.'${2}',
                $html,
                1,
            ) ?? $html;
        }

        $country = strtoupper((string) ($user->country ?? ''));
        $html = preg_replace_callback(
            '/(<select\b[^>]*\bname="country"[^>]*>.*?<\/select>)/is',
            function (array $matches) use ($country): string {
                $select = preg_replace('/\sselected\b/i', '', $matches[1]) ?? $matches[1];
                $value = preg_quote($country !== '' ? $country : '#offline', '/');

                return preg_replace(
                    '/(<option\b[^>]*\bvalue="'.$value.'"[^>]*)>/i',
                    '$1 selected>',
                    $select,
                    1,
                ) ?? $select;
            },
            $html,
            1,
        ) ?? $html;

        $message = '';
        $errors = session('errors');
        if ($errors && $errors->any()) {
            $message = '<div class="alert alert-danger mb-3" role="alert">'.e($errors->first()).'</div>';
        } elseif (is_string(session('status'))) {
            $message = '<div class="alert alert-success mb-3" role="status">'.e(session('status')).'</div>';
        }

        if ($message !== '') {
            $html = preg_replace(
                '/(<form\b[^>]*\bclass="stripe-create"[^>]*>)/i',
                '$1'.$message,
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }

    protected function applyAuthenticatedNavigation(string $html, string $page): string
    {
        $homePages = [
            'index.html',
            'en/index.html',
            'en/subscriptions.html',
            'en/subscriptions/house.html',
            'en/brand/amazon.html',
        ];

        if (! Auth::check() || ! in_array($page, $homePages, true)) {
            return $html;
        }

        $dashboard = $this->readPage('en/dashboard.html');
        if (! preg_match(
            '/<ul class="navbar-nav ml-auto">(.*?)<li class="nav-item d-flex">\s*<div class="dropdown lang-dropdown/s',
            $dashboard,
            $dashboardMatch,
        )) {
            return $html;
        }

        $shareButton = <<<'HTML'
<li class="nav-item">
    <a class="btn-sharesub green" href="/en/register.html">Share my subscription</a>
</li>
HTML;
        $authenticatedItems = $shareButton.$dashboardMatch[1];

        $html = preg_replace_callback(
            '/(<ul class="navbar-nav ml-auto">).*?(<li class="nav-item d-flex">\s*<div class="dropdown lang-dropdown)/s',
            fn (array $current): string => $current[1].$authenticatedItems.$current[2],
            $html,
            1,
        ) ?? $html;

        if (! str_contains($html, 'id="logout-form"')) {
            $logoutForm = '<form id="logout-form" action="/en/logout" method="POST" style="display:none;">'
                .'<input type="hidden" name="_token" value="'.csrf_token().'">'
                .'</form>';
            $html = str_replace('</body>', $logoutForm.'</body>', $html);
        }

        return $html;
    }

    protected function readPage(string $page): string
    {
        if (! $this->pageExists($page)) {
            abort(404);
        }

        return file_get_contents(resource_path('captured/'.ltrim($page, '/')));
    }

    protected function pageExists(string $page): bool
    {
        $relative = ltrim(str_replace(['..', '\\'], '', $page), '/');

        return is_file(resource_path('captured/'.$relative));
    }

    protected function replaceCsrfTokens(string $html): string
    {
        return preg_replace(
            '/(name="_token"\s+value=")[^"]*(")/i',
            '$1'.csrf_token().'$2',
            $html,
        ) ?? $html;
    }

    protected function normalizePageLinks(string $html): string
    {
        return preg_replace_callback(
            '/\b(href|action)=(")?(\/(?:en|cms\/en)\/[^"\'\s>]*?)\.html((?:[?#][^"\']*)?)(")/i',
            static function (array $match): string {
                $path = $match[3];
                if (str_ends_with($path, '/index')) {
                    $path = substr($path, 0, -6);
                }

                return $match[1].'="'.$path.$match[4].'"';
            },
            $html,
        ) ?? $html;
    }
}
