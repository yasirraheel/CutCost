<?php

namespace App\Http\Controllers\Concerns;

use App\Models\FooterLinkGroup;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait RendersSiteChrome
{
    protected $dynamicReady = null;
    protected $dynamicSettings = null;

    /**
     * The one shared header and footer rendering path. Every captured
     * surface goes through here: public pages, dashboard pages, auth
     * pages and the create subscription wizard fragments. Nav labels,
     * footer tagline, copyright and social links, the locale dropdown
     * and the header SEO bits are replaced once, in one place, so all
     * surfaces stay consistent. Footer link columns are replaced only
     * on the three public pages that already used them, matching the
     * previous behaviour: the brand template keeps its own captured
     * footer columns, since its Hot topics links have no database
     * equivalent, and the dashboard templates keep theirs. A failure
     * in one piece never breaks the page.
     */
    public function applySiteChrome(string $html, ?string $page = null): string
    {
        $original = $html;

        try {
            $html = $this->replaceNavLabels($html);
            $html = $this->replaceFooterTagline($html);
            $html = $this->replaceFooterCopyright($html);
            $html = $this->replaceFooterSocial($html);
            $html = $this->replaceLocalesDropdown($html, $page ?? '');
            $html = $this->replaceDefaultLocale($html);
            $html = $this->replaceSeoMeta($html, $page ?? '');
            if ($page !== null && in_array($page, ['en/index.html', 'en/subscriptions.html', 'en/subscriptions/house.html'], true)) {
                $html = $this->replaceFooterLinks($html);
            }
            $html = $this->replaceShareSubscriptionLinks($html);
            $html = $this->injectSupportChatWidget($html);
            $html = $this->replaceCreateAccountButtons($html);
            $html = $this->removeDeadRecaptchaInputs($html);
        } catch (\Throwable) {
            return $original;
        }

        return $html;
    }

    /**
     * Strip the hidden g-recaptcha-response inputs from rendered pages.
     * reCAPTCHA is not wired up in this replica: no page loads the
     * Google script, nothing ever sets window.sharesubRecaptcha, and
     * the server never validates the token. The captured app.js guards
     * every form carrying that input: on submit it prevents the native
     * submit and re-submits from its token callback. Without reCAPTCHA
     * the callback runs synchronously inside the submit dispatch, and
     * the browser drops that nested re-submit, so the form can never
     * be submitted by clicking its button once app.js has loaded
     * (login and register are both affected). Removing the dead input
     * means the guard never attaches and the forms submit normally.
     * If reCAPTCHA is ever configured (loader plus server check),
     * remove this strip and let the input through again.
     */
    protected function removeDeadRecaptchaInputs(string $html): string
    {
        return preg_replace('/<input\b[^>]*\bname="g-recaptcha-response"[^>]*>/i', '', $html) ?? $html;
    }

    /**
     * Point every share, propose and create subscription call to action
     * at the wizard instead of the registration page or a dead link.
     * Only anchors whose visible text is one of the share CTAs are
     * rewritten, so the genuine Register and Login nav links, the
     * footer Hot topics article links and the blog post links on brand
     * pages are left exactly as captured. Both href shapes are handled:
     * /en/register.html on captured pages and /en/register on CMS pages.
     */
    protected function replaceShareSubscriptionLinks(string $html): string
    {
        $labels = [
            'Share my subscription',
            'Propose a subscription',
            'Create subscription',
            'Share a Subscription',
            'Got free spots? Share your subscription',
            'I\'m sharing my subscription',
        ];

        return preg_replace_callback(
            '/<a\b[^>]*href="(?:\/en\/register(?:\.html)?|#offline)"[^>]*>.*?<\/a>/is',
            static function (array $match) use ($labels): string {
                $anchor = $match[0];

                if (! preg_match('/<a\b[^>]*>(.*)<\/a>/is', $anchor, $inner)) {
                    return $anchor;
                }

                $text = trim((string) preg_replace('/\s+/', ' ', strip_tags($inner[1])));

                $isShareCardButton = $text === 'Share'
                    && str_contains($anchor, 'btn-sharesub dark big');

                if (! in_array($text, $labels, true) && ! $isShareCardButton) {
                    return $anchor;
                }

                return (string) preg_replace(
                    '/href="(?:\/en\/register(?:\.html)?|#offline)"/',
                    'href="/en/create-subscription"',
                    $anchor,
                    1,
                );
            },
            $html,
        ) ?? $html;
    }

    /**
     * Load the bottom-right support chat widget script on logged-in
     * non-admin pages. The script builds the panel itself from the
     * captured chat classes and talks to the support endpoints; admin
     * users get the support inbox instead, so they never load it.
     */
    protected function injectSupportChatWidget(string $html): string
    {
        if (! Auth::check() || Auth::user()->isAdmin()) {
            return $html;
        }

        $position = strrpos($html, '</body>');

        if ($position === false) {
            return $html;
        }

        return substr($html, 0, $position)
            .'<script src="/js/support-chat.js" defer></script>'."\n"
            .substr($html, $position);
    }

    /**
     * The "Create my account" calls to action are dead links in the
     * capture (#offline): the hero see-all button and the second call
     * to action block on the homepage. A guest goes to the
     * registration page; a logged in user already has an account, so
     * the buttons are removed instead of pointing anywhere. The match
     * stays inside one anchor (an <img> sits before the label in the
     * call to action button), so a #offline link earlier on the page
     * can never swallow content up to the label.
     */
    protected function replaceCreateAccountButtons(string $html): string
    {
        $loggedIn = Auth::check();

        return preg_replace_callback(
            '/<a\b(?=[^>]*\bclass="[^"]*\bbtn-sharesub\b[^"]*")[^>]*href="(?:#offline|\/en\/register(?:\.html)?)"[^>]*>(?:(?!<\/a>).)*?Create my account\s*<\/a>/is',
            static function (array $match) use ($loggedIn): string {
                if ($loggedIn) {
                    return '';
                }

                return (string) preg_replace(
                    '/href="(?:#offline|\/en\/register(?:\.html)?)"/',
                    'href="/en/register"',
                    $match[0],
                    1,
                );
            },
            $html,
        ) ?? $html;
    }

    /**
     * True when a table holds at least one row, active or not. This is
     * the fallback rule: a section falls back to the captured HTML
     * only when its table is empty. A count query keeps it cheap, and
     * a missing table or database error reads as empty.
     */
    protected function tableHasRows(string $table): bool
    {
        try {
            return DB::table($table)->count() > 0;
        } catch (\Throwable) {
            return false;
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
            'cta1_title', 'cta1_text', 'cta1_button_label',
            'cta2_title', 'cta2_text', 'cta2_button_label',
            'hero_rating_line', 'hero_create_account_label',
            'testimonials_heading', 'testimonials_subheading',
            'faq_section_title', 'press_section_title', 'features_read_more_label',
            'catalogue_see_all_label', 'catalogue_services_count', 'house_title_prefix',
            'footer_tagline', 'footer_copyright',
            'footer_social_tiktok', 'footer_social_youtube', 'footer_social_linkedin',
            'footer_social_instagram', 'footer_social_facebook', 'footer_social_x',
            'nav_share_label', 'nav_subscriptions_label', 'nav_blog_label',
            'nav_register_label', 'nav_login_label', 'nav_menu_label',
            'nav_propose_label', 'nav_browse_label', 'nav_help_label',
            'locales_json', 'default_locale', 'fallback_locale',
            'seo_home_title', 'seo_home_description',
            'seo_catalogue_title', 'seo_catalogue_description', 'seo_house_description',
            'jsonld_app_name', 'jsonld_rating_value', 'jsonld_rating_count',
            'calculator_avg_yearly_savings', 'calculator_default_brand_slug',
            'how_title', 'how_section_title', 'how_share_label', 'how_seat_label',
            'dashboard_cta_title',
            'dashboard_checklist_1_title', 'dashboard_checklist_1_text',
            'dashboard_checklist_2_title', 'dashboard_checklist_2_text',
            'dashboard_checklist_3_title', 'dashboard_checklist_3_text',
            'shared_empty_text', 'joined_empty_text',
            'waiting_help_1_title', 'waiting_help_1_text',
            'waiting_help_2_title', 'waiting_help_2_text',
            'waiting_help_3_title', 'waiting_help_3_text',
            'wallet_policy_1', 'wallet_policy_2',
            'chat_empty_title', 'chat_empty_text',
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

    protected function replaceNavLabels(string $html): string
    {
        $labels = [
            'nav_share_label' => '/(<a class="btn-sharesub green" href="\/en\/register\.html">)[^<]*(<\/a>)/',
            'nav_subscriptions_label' => '/(<a class="p1-semibold lg:p2-regular" href="\/en\/subscriptions\.html">)[^<]*(<\/a>)/',
            'nav_blog_label' => '/(<a class="p1-semibold lg:p2-regular" href="\/cms\/en\/index\.html">)[^<]*(<\/a>)/',
            'nav_register_label' => '/(<a class="p1-semibold lg:p2-regular" href="\/en\/register\.html">)[^<]*(<\/a>)/',
            'nav_login_label' => '/(<a class="p1-semibold lg:p2-regular" href="\/en\/userlogin\.html">)[^<]*(<\/a>)/',
            'nav_propose_label' => '/(<a href="\/en\/register\.html" class="btn btn-sharesub green">)[^<]*(<\/a>)/',
            'nav_browse_label' => '/(<a href="\/en\/subscriptions\.html" class="btn btn-sharesub blue">)[^<]*(<\/a>)/',
            'nav_help_label' => '/(<a href="#offline" target="_blank">)[^<]*(<\/a>)/',
        ];

        foreach ($labels as $key => $pattern) {
            $label = $this->dynamicSetting($key);
            if ($label === null) {
                continue;
            }
            $html = preg_replace_callback(
                $pattern,
                static fn (array $matches): string => $matches[1].e($label).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        $menu = $this->dynamicSetting('nav_menu_label');
        if ($menu !== null) {
            $html = preg_replace_callback(
                '/(<button class="navbar-toggler[^>]*>)\s*[^<\n]+(\s*<img)/',
                static fn (array $matches): string => $matches[1]."\n        ".e($menu).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }

    protected function replaceFooterTagline(string $html): string
    {
        $tagline = $this->dynamicSetting('footer_tagline');

        if ($tagline === null) {
            return $html;
        }

        $rendered = str_replace('{heart}', '❤️', $tagline);

        return preg_replace_callback(
            '/(<div class="mb-4 col-12">)Made with[^<]*(<\/div>)/',
            static fn (array $matches): string => $matches[1].e($rendered).$matches[2],
            $html,
            1,
        ) ?? $html;
    }

    protected function replaceFooterCopyright(string $html): string
    {
        $copyright = $this->dynamicSetting('footer_copyright');

        if ($copyright === null) {
            return $html;
        }

        return preg_replace_callback(
            '/(<div class="bottom">\s*<div class="container-fluid">\s*<div>)[^<]*(<\/div>)/',
            static fn (array $matches): string => $matches[1].e($copyright).$matches[2],
            $html,
            1,
        ) ?? $html;
    }

    /**
     * Footer social icons. The captured index footer social div is empty
     * and the icons in the other pages carry no link, so nothing renders
     * until an admin sets a URL. Icon markup is the captured one from
     * subscriptions.html, with the span swapped for a link.
     */
    protected function replaceFooterSocial(string $html): string
    {
        if (! str_contains($html, 'footer-social')) {
            return $html;
        }

        $networks = [
            'footer_social_tiktok' => ['tiktok', 'Tiktok'],
            'footer_social_youtube' => ['youtube', 'YouTube'],
            'footer_social_linkedin' => ['linkedin', 'Linked In'],
            'footer_social_instagram' => ['instagram', 'Instagram'],
            'footer_social_facebook' => ['facebook', 'Facebook'],
            'footer_social_x' => ['x', 'X'],
        ];

        $links = '';
        foreach ($networks as $key => [$file, $alt]) {
            $url = $this->dynamicSetting($key);
            if ($url === null) {
                continue;
            }
            $links .= '<a href="'.e($url).'" target="_blank" rel="noopener" class="cursor-pointer">'."\n"
                .'<img src="/img/social/'.$file.'.svg" alt="'.e($alt).'">'."\n"
                .'</a>'."\n";
        }

        if ($links === '') {
            return $html;
        }

        return preg_replace_callback(
            '/(<div class="mb-4 col-12 footer-social d-flex align-items-center">).*?(<\/div>)/s',
            static fn (array $matches): string => $matches[1]."\n".$links.$matches[2],
            $html,
            1,
        ) ?? $html;
    }

    protected function replaceLocalesDropdown(string $html, string $page): string
    {
        $json = $this->dynamicSetting('locales_json');

        if ($json === null) {
            return $html;
        }

        $locales = json_decode($json, true);
        if (! is_array($locales) || $locales === []) {
            return $html;
        }

        $currentHref = [
            'en/index.html' => '/en/index.html',
            'en/subscriptions.html' => '/en/subscriptions.html',
            'en/subscriptions/house.html' => '/en/subscriptions/house.html',
            'en/brand/amazon.html' => '/en/brand/amazon.html',
        ][$page] ?? null;

        $items = '';
        foreach ($locales as $locale) {
            $code = (string) ($locale['code'] ?? '');
            $label = (string) ($locale['label'] ?? '');
            $url = (string) ($locale['url'] ?? '#offline');
            if ($code === '' || $label === '') {
                continue;
            }
            $current = $currentHref !== null && $url === $currentHref ? ' current' : '';
            $items .= '<a class="dropdown-item'.$current.'" rel="alternate" href="'.e($url).'">'."\n"
                .'<img src="/img/lang/'.e($code).'.svg" loading="lazy" alt="'.e($label.' flag').'" />'."\n"
                .'<span>'.e($label).'</span>'."\n"
                .'</a>'."\n";
        }

        if ($items === '') {
            return $html;
        }

        return preg_replace_callback(
            '/(<div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink">).*?(<\/div>\s*<\/div>)/s',
            static fn (array $matches): string => $matches[1]."\n".$items.$matches[2],
            $html,
        ) ?? $html;
    }

    protected function replaceDefaultLocale(string $html): string
    {
        $default = $this->dynamicSetting('default_locale');
        if ($default !== null) {
            $html = preg_replace(
                '/(window\.default_locale\s*=\s*")[^"]*(")/',
                '$1'.e($default).'$2',
                $html,
                1,
            ) ?? $html;
        }

        $fallback = $this->dynamicSetting('fallback_locale');
        if ($fallback !== null) {
            $html = preg_replace(
                '/(window\.fallback_locale\s*=\s*")[^"]*(")/',
                '$1'.e($fallback).'$2',
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }

    protected function replaceSeoMeta(string $html, string $page): string
    {
        $mapping = [
            'en/index.html' => ['seo_home_title', 'seo_home_description'],
            'en/subscriptions.html' => ['seo_catalogue_title', 'seo_catalogue_description'],
            'en/subscriptions/house.html' => [null, 'seo_house_description'],
        ];

        if (! isset($mapping[$page])) {
            return $html;
        }

        [$titleKey, $descriptionKey] = $mapping[$page];

        if ($titleKey !== null) {
            $title = $this->dynamicSetting($titleKey);
            if ($title !== null) {
                $html = preg_replace_callback(
                    '/(<title>)[^<]*(<\/title>)/',
                    static fn (array $matches): string => $matches[1].e($title).$matches[2],
                    $html,
                    1,
                ) ?? $html;
            }
        }

        $description = $this->dynamicSetting($descriptionKey);
        if ($description !== null) {
            $html = preg_replace_callback(
                '/(<meta name="description" content=")[^"]*(")/',
                static fn (array $matches): string => $matches[1].e($description).$matches[2],
                $html,
                1,
            ) ?? $html;
        }

        return $html;
    }

    /**
     * Footer link columns. The first two groups render in the middle
     * column, each as a col-lg-6 block like the capture; any further
     * groups render in the right hand column, like the captured Hot
     * topics column. Groups with no active links still render their
     * title, like Hot topics does in the capture, except when no group
     * for the right hand column has any links at all: the Hot topics
     * article links have no database equivalent, so the captured right
     * hand column is kept exactly as captured instead of being wiped
     * to a bare title.
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
        $rightHasLinks = false;
        foreach ($groups as $index => $group) {
            if ($index < 2) {
                $middle .= $renderGroup($group, true);
            } else {
                $right .= $renderGroup($group, false);
                if ($group->footerLinks->isNotEmpty()) {
                    $rightHasLinks = true;
                }
            }
        }

        // One anchored pass from the middle column to the end of the
        // footer links row. The first column (Made with...) shares the
        // right hand column class, so the right column must not be
        // matched on its own or the whole row gets wiped.
        $rightColumn = '<div class="col-12 col-md-4 col-lg-3 my-3">'."\n".$right;

        return preg_replace_callback(
            '/(<div class="col-12 col-md-4 col-lg-6">\s*<div class="row">)(.*?)(<\/div>\s*<\/div>\s*<\/section>)/s',
            static function (array $matches) use ($middle, $rightColumn, $rightHasLinks): string {
                $right = $rightColumn;
                if (! $rightHasLinks) {
                    // Keep the captured right hand column (its opening div
                    // and inner links, without its closing div, which the
                    // shared closers below still supply).
                    if (preg_match('/(<div class="col-12 col-md-4 col-lg-3 my-3">.*?)(<\/div>)\s*$/s', $matches[2], $captured)) {
                        $right = $captured[1];
                    }
                }

                return $matches[1]."\n".$middle."</div>\n                    </div>\n                    ".$right.$matches[3];
            },
            $html,
            1,
        ) ?? $html;
    }
}
