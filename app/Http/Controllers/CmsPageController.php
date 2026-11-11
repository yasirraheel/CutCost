<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class CmsPageController extends Controller
{
    /** @var array<string, string> */
    private const PAGES = [
        'index.html' => 'index.html',
        'team/index.html' => 'team/index.html',
        'legal/index.html' => 'legal/index.html',
        'legal/tos/index.html' => 'legal/tos/index.html',
        'buyer-protection-program/index.html' => 'buyer-protection-program/index.html',
        'duolingo-family-plan-cost-current-discounts-on-duolingo-super-max/index.html'
            => 'duolingo-family-plan-cost-current-discounts-on-duolingo-super-max/index.html',
    ];

    /**
     * Captured file used as the template for a database page that has no
     * captured file of its own. It is a plain content page, so its header,
     * footer and navigation markup stand in for the missing template.
     */
    private const GENERIC_TEMPLATE = 'team/index.html';

    public function show(string $page): Response
    {
        $slug = $this->slugForPage($page);
        $dbPage = $this->findActivePage($slug);

        if ($slug === 'index') {
            $posts = $this->activePosts();
            if ($posts->isNotEmpty() || $this->hasPostRows()) {
                return $this->renderIndex($posts, $dbPage);
            }
        } elseif ($dbPage !== null) {
            return $this->renderDbPage($dbPage, $page);
        } elseif ($this->hasPageRow($slug)) {
            // A row exists for this slug but none is active: the page
            // is hidden instead of falling back to the captured file.
            abort(404);
        }

        abort_unless(isset(self::PAGES[$page]), 404);

        $file = resource_path('captured/cms/en/'.self::PAGES[$page]);
        abort_unless(is_file($file), 404);

        $html = file_get_contents($file);
        $html = $this->normalizeLinks($html);
        $html = $this->fixSpeculationRules($html);
        $html = $this->replaceShareSubscriptionLinks($html);
        $html = $this->replaceMyAccountLink($html);

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    /**
     * Point every share call to action on CMS pages at the wizard
     * instead of the registration page. The CMS header carries a
     * "Share a Subscription" menu link and some pages carry a "Got
     * free spots? Share your subscription" banner link, both pointing
     * at /en/register. Only anchors whose visible text is a share CTA
     * are rewritten, so no other CMS link is touched.
     */
    protected function replaceShareSubscriptionLinks(string $html): string
    {
        $labels = [
            'Share a Subscription',
            'Got free spots? Share your subscription',
            'Share my subscription',
            'Propose a subscription',
            'Create subscription',
        ];

        return preg_replace_callback(
            '/<a\b[^>]*href="(?:\/en\/register(?:\.html)?|#offline)"[^>]*>.*?<\/a>/is',
            static function (array $match) use ($labels): string {
                $anchor = $match[0];

                if (! preg_match('/<a\b[^>]*>(.*)<\/a>/is', $anchor, $inner)) {
                    return $anchor;
                }

                $text = trim((string) preg_replace('/\s+/', ' ', strip_tags($inner[1])));

                if (! in_array($text, $labels, true)) {
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
     * The CMS header carries a "My account" menu link pointing at
     * /en/userlogin. Guests keep it; a logged in user goes to their
     * dashboard instead, and an admin goes to the admin panel, so
     * the link never lands on the guest-middleware redirect.
     */
    protected function replaceMyAccountLink(string $html): string
    {
        if (! Auth::check()) {
            return $html;
        }

        if (Auth::user()->isAdmin()) {
            return str_replace(
                '<a href="/en/userlogin">My account</a>',
                '<a href="/en/admin">Admin panel</a>',
                $html,
            );
        }

        return str_replace(
            '<a href="/en/userlogin">My account</a>',
            '<a href="/en/dashboard">My account</a>',
            $html,
        );
    }

    /**
     * The blog index. When any active posts exist, the captured index is
     * kept as the template and its articles grid is replaced with cards
     * for the database posts. When posts exist but none are active, the
     * grid renders empty. Otherwise the captured index is returned
     * exactly as it is.
     */
    protected function renderIndex(mixed $posts, ?Page $indexPage): Response
    {
        $file = resource_path('captured/cms/en/'.self::PAGES['index.html']);
        abort_unless(is_file($file), 404);

        $html = file_get_contents($file);
        $html = $this->normalizeLinks($html);
        $html = $this->fixSpeculationRules($html);
        $html = $this->replaceShareSubscriptionLinks($html);

        if ($indexPage !== null) {
            $html = $this->replaceTitleTag($html, (string) $indexPage->title);
        }

        $cards = '';
        foreach ($posts as $post) {
            $url = '/cms/en/'.(string) $post->slug;
            $cards .= '<article class="default grid-50 tablet-grid-50 mobile-grid-100 post type-post status-publish format-standard hentry">'."\n"
                .'<div class="info post-style-small-image epcl-flex">'."\n"
                .'<h2 class="main-title title underline-effect large"><a href="'.e($url).'">'.e((string) $post->title).'</a></h2>'."\n";
            if (! empty($post->excerpt)) {
                $cards .= '<div class="post-excerpt"><p>'.e((string) $post->excerpt).'</p></div>'."\n";
            }
            $cards .= '</div>'."\n"
                .'</article>'."\n";
        }

        $html = $this->replaceArticlesGrid($html, $cards);

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    /**
     * A database page rendered inside its captured template. The stored
     * title and body replace the captured title, heading and post content;
     * the header, footer and navigation markup stay exactly as captured.
     * A page with no captured file of its own uses the generic template.
     */
    protected function renderDbPage(Page $page, string $requested): Response
    {
        $template = self::PAGES[$requested] ?? self::GENERIC_TEMPLATE;
        $file = resource_path('captured/cms/en/'.$template);
        abort_unless(is_file($file), 404);

        $html = file_get_contents($file);
        $html = $this->normalizeLinks($html);
        $html = $this->fixSpeculationRules($html);
        $html = $this->replaceShareSubscriptionLinks($html);
        $html = $this->replaceMyAccountLink($html);
        $html = $this->replaceTitleTag($html, (string) $page->title);
        $html = preg_replace_callback(
            '/(<h1\b[^>]*>).*?(<\/h1>)/s',
            static fn (array $matches): string => $matches[1].e((string) $page->title).$matches[2],
            $html,
            1,
        ) ?? $html;

        $body = trim((string) $page->body_html);
        if ($body !== '' || ! empty($page->image_path)) {
            $image = '';
            if (! empty($page->image_path)) {
                $image = '<p><img src="'.e('/storage/'.ltrim((string) $page->image_path, '/')).'" alt="'.e((string) $page->title).'"></p>'."\n";
            }
            $html = $this->replacePostContent($html, $image.$body);
        }

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    protected function replaceTitleTag(string $html, string $title): string
    {
        return preg_replace_callback(
            '/(<title>)([^<]*)(<\/title>)/s',
            static fn (array $matches): string => $matches[1].e($title).$matches[3],
            $html,
            1,
        ) ?? $html;
    }

    protected function replacePostContent(string $html, string $body): string
    {
        if (! preg_match('/<section\b[^>]*post-content[^>]*>/is', $html, $match, PREG_OFFSET_CAPTURE)) {
            return $html;
        }

        $start = $match[0][1];
        $tagLength = strlen($match[0][0]);
        preg_match_all('/<\/?section\b[^>]*>/i', $html, $tags, PREG_OFFSET_CAPTURE, $start);

        $depth = 0;
        foreach ($tags[0] as [$tag, $offset]) {
            $depth += str_starts_with(strtolower($tag), '</section') ? -1 : 1;
            if ($depth === 0) {
                return substr($html, 0, $start + $tagLength).$body.substr($html, $offset);
            }
        }

        return $html;
    }

    protected function replaceArticlesGrid(string $html, string $cards): string
    {
        $marker = '<div class="articles grid-posts grid-sidebar columns-2">';
        $start = strpos($html, $marker);
        if ($start === false) {
            return $html;
        }

        preg_match_all('/<\/?div\b[^>]*>/i', $html, $tags, PREG_OFFSET_CAPTURE, $start);

        $depth = 0;
        foreach ($tags[0] as [$tag, $offset]) {
            $depth += str_starts_with(strtolower($tag), '</div') ? -1 : 1;
            if ($depth === 0) {
                return substr($html, 0, $start + strlen($marker))."\n".$cards.substr($html, $offset);
            }
        }

        return $html;
    }

    /**
     * Repair the speculation rules JSON in the captured CMS templates.
     * Every template carries the same broken selector string: a literal
     * line break inside ".no-prefetch,\n.no-prefetch a", which is not
     * valid JSON, so the browser rejects the whole prefetch rules block
     * ("Line: 2, column: 412, Syntax error"). A comma plus a space is
     * the same selector list, so the break is replaced with a space.
     */
    protected function fixSpeculationRules(string $html): string
    {
        return preg_replace('/("\.no-prefetch,)\s*\n\s*/', '$1 ', $html) ?? $html;
    }

    protected function normalizeLinks(string $html): string
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

    /**
     * Route path to page slug: 'index.html' is 'index', a captured path
     * like 'team/index.html' is 'team', and a slug from the generic
     * /cms/en/{page} route is already the slug.
     */
    protected function slugForPage(string $page): string
    {
        if (! isset(self::PAGES[$page])) {
            return $page;
        }

        $path = self::PAGES[$page];
        if ($path === 'index.html') {
            return 'index';
        }

        return preg_replace('#/index\.html$#', '', $path) ?? $path;
    }

    protected function findActivePage(string $slug): ?Page
    {
        if (! $this->pagesReady()) {
            return null;
        }

        try {
            return Page::where('slug', $slug)->where('is_active', true)->first();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function activePosts(): mixed
    {
        if (! $this->pagesReady()) {
            return collect();
        }

        try {
            return Page::active()->where('type', 'post')->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    /**
     * True when a row exists for a slug, active or not. A deactivated
     * page hides instead of showing its captured file.
     */
    protected function hasPageRow(string $slug): bool
    {
        if (! $this->pagesReady()) {
            return false;
        }

        try {
            return Page::where('slug', $slug)->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * True when at least one post row exists, active or not. The blog
     * index renders an empty grid when posts exist but none are active,
     * and keeps the captured index only when no post was ever seeded.
     */
    protected function hasPostRows(): bool
    {
        if (! $this->pagesReady()) {
            return false;
        }

        try {
            return Page::where('type', 'post')->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * True when the pages model and table exist. Lets the public CMS
     * pages keep serving the captured HTML before the pages migration
     * has run. A database error never breaks a public page.
     */
    protected function pagesReady(): bool
    {
        try {
            return class_exists(Page::class) && Schema::hasTable('pages');
        } catch (\Throwable) {
            return false;
        }
    }
}
