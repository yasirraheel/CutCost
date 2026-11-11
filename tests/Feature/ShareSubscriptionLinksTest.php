<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareSubscriptionLinksTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function makeAmazonBrand(): void
    {
        Brand::create([
            'name' => 'Amazon',
            'slug' => 'amazon',
            'price' => 3.21,
            'currency' => 'GBP',
            'currency_symbol' => '£',
            'discount_percent' => 50,
            'background' => '#FF8900',
            'icon_svg' => '<svg width="24" height="24" viewBox="0 0 24 24"><path d="M1 1h22v22H1z"/></svg>',
            'is_featured' => true,
            'sort_order' => 0,
            'is_active' => true,
        ]);
    }

    /**
     * Share, propose and create subscription calls to action must never
     * point at the registration page or a dead link.
     */
    private function assertNoDeadShareLinks(string $html, string $page): void
    {
        $labels = [
            'Share my subscription',
            'Propose a subscription',
            'Create subscription',
            'Create a shared Subscription',
            'Share a Subscription',
            'Got free spots? Share your subscription',
            'I\'m sharing my subscription',
        ];

        preg_match_all(
            '/<a\b[^>]*href="([^"]*)"[^>]*>(.*?)<\/a>/is',
            $html,
            $anchors,
            PREG_SET_ORDER,
        );

        foreach ($anchors as $anchor) {
            $href = $anchor[1];
            $text = trim((string) preg_replace('/\s+/', ' ', strip_tags($anchor[2])));
            $isShareCardButton = $text === 'Share'
                && str_contains($anchor[0], 'btn-sharesub dark big');

            if (! in_array($text, $labels, true) && ! $isShareCardButton) {
                continue;
            }

            $this->assertNotContains(
                $href,
                ['/en/register', '/en/register.html', '#offline'],
                "On {$page} the '{$text}' link still points at {$href}.",
            );
        }
    }

    private function assertShareLinkPointsAtWizard(string $html, string $label, string $page): void
    {
        preg_match_all(
            '/<a\b[^>]*href="([^"]*)"[^>]*>(.*?)<\/a>/is',
            $html,
            $anchors,
            PREG_SET_ORDER,
        );

        $found = false;
        foreach ($anchors as $anchor) {
            $text = trim((string) preg_replace('/\s+/', ' ', strip_tags($anchor[2])));
            if ($text === $label) {
                $found = true;
                $this->assertSame(
                    '/en/create-subscription',
                    $anchor[1],
                    "On {$page} the '{$label}' link points at {$anchor[1]}.",
                );
            }
        }

        $this->assertTrue($found, "On {$page} no '{$label}' link was found at all.");
    }

    public function test_guest_homepage_header_share_link_points_at_wizard(): void
    {
        $html = $this->get('/en')->assertOk()->getContent();

        $this->assertNoDeadShareLinks($html, 'guest homepage');
        $this->assertShareLinkPointsAtWizard($html, 'Share my subscription', 'guest homepage');
        $this->assertShareLinkPointsAtWizard($html, 'Propose a subscription', 'guest homepage');

        // The genuine Register nav link stays as is. The footer Hot
        // topics article links are not share calls to action either and
        // stay as captured; they live on the brand and account pages,
        // not on the homepage (the homepage capture has a bare Hot
        // topics title), so they are checked on the brand page.
        $this->assertStringContainsString('href="/en/register">Register</a>', $html);
        $this->assertStringContainsString('Hot topics', $html);

        $this->makeAmazonBrand();
        $brand = $this->get('/en/brand/amazon')->assertOk()->getContent();
        $this->assertStringContainsString('href="#offline">Share on Spotify</a>', $brand);
        $this->assertStringContainsString('href="#offline">Share a Disney+ Account</a>', $brand);
    }

    public function test_logged_in_homepage_brand_and_catalogue_share_links_point_at_wizard(): void
    {
        $this->makeAmazonBrand();
        $this->actingAs($this->user());

        $home = $this->get('/en')->assertOk()->getContent();
        $this->assertNoDeadShareLinks($home, 'logged in homepage');
        $this->assertShareLinkPointsAtWizard($home, 'Share my subscription', 'logged in homepage');

        $brand = $this->get('/en/brand/amazon')->assertOk()->getContent();
        $this->assertNoDeadShareLinks($brand, 'brand page');
        $this->assertShareLinkPointsAtWizard($brand, 'Share my subscription', 'brand page');
        $this->assertShareLinkPointsAtWizard($brand, 'Propose a subscription', 'brand page');

        $catalogue = $this->get('/en/subscriptions')->assertOk()->getContent();
        $this->assertNoDeadShareLinks($catalogue, 'catalogue');
        $this->assertShareLinkPointsAtWizard($catalogue, 'Propose a subscription', 'catalogue');

        $house = $this->get('/en/subscriptions/house')->assertOk()->getContent();
        $this->assertNoDeadShareLinks($house, 'house page');
        $this->assertShareLinkPointsAtWizard($house, 'Propose a subscription', 'house page');
    }

    public function test_dashboard_and_chat_share_links_point_at_wizard(): void
    {
        $this->actingAs($this->user());

        $dashboard = $this->get('/en/dashboard')->assertOk()->getContent();
        $this->assertNoDeadShareLinks($dashboard, 'dashboard');
        $this->assertShareLinkPointsAtWizard($dashboard, 'Propose a subscription', 'dashboard');

        $chat = $this->get('/en/my-chat/subscriptions')->assertOk()->getContent();
        $this->assertNoDeadShareLinks($chat, 'chat page');
        $this->assertShareLinkPointsAtWizard($chat, 'Propose a subscription', 'chat page');
        $this->assertShareLinkPointsAtWizard($chat, 'Create subscription', 'chat page');

        $shared = $this->get('/en/my-shared-subscriptions')->assertOk()->getContent();
        $this->assertNoDeadShareLinks($shared, 'my shared subscriptions');
        $this->assertShareLinkPointsAtWizard($shared, 'Create a shared Subscription', 'my shared subscriptions');
        $this->assertShareLinkPointsAtWizard($shared, 'Propose a subscription', 'my shared subscriptions');
    }

    public function test_auth_and_cms_pages_share_links_point_at_wizard(): void
    {
        $login = $this->get('/en/userlogin')->assertOk()->getContent();
        $this->assertNoDeadShareLinks($login, 'login page');
        $this->assertShareLinkPointsAtWizard($login, 'Propose a subscription', 'login page');

        $register = $this->get('/en/register')->assertOk()->getContent();
        $this->assertNoDeadShareLinks($register, 'register page');
        $this->assertShareLinkPointsAtWizard($register, 'Propose a subscription', 'register page');

        $blog = $this->get('/cms/en')->assertOk()->getContent();
        $this->assertNoDeadShareLinks($blog, 'CMS blog index');
        $this->assertShareLinkPointsAtWizard($blog, 'Share a Subscription', 'CMS blog index');

        $team = $this->get('/cms/en/team')->assertOk()->getContent();
        $this->assertNoDeadShareLinks($team, 'CMS team page');
        $this->assertShareLinkPointsAtWizard($team, 'Share a Subscription', 'CMS team page');
    }

    /**
     * The rendered login and register pages must not carry the dead
     * g-recaptcha-response input: the captured app.js guards every form
     * with that input and, with reCAPTCHA not wired up, the guard
     * swallows the submit so the form can never be posted by clicking
     * its button. The forms themselves must stay intact.
     */
    public function test_auth_pages_have_no_dead_recaptcha_input(): void
    {
        foreach (['/en/userlogin', '/en/register'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('g-recaptcha-response', $html, "Dead reCAPTCHA input left on {$url}.");
            $this->assertStringContainsString('name="email"', $html, "Email field missing on {$url}.");
            $this->assertStringContainsString('name="password"', $html, "Password field missing on {$url}.");
            $this->assertStringContainsString('type="submit"', $html, "Submit button missing on {$url}.");
        }
    }

    /**
     * The speculation rules block in the rendered CMS pages must be
     * valid JSON: the captured templates carry a literal line break
     * inside the ".no-prefetch" selector string, which the browser
     * rejects as a syntax error on every CMS page.
     */
    public function test_cms_speculation_rules_json_is_valid(): void
    {
        foreach (['/cms/en', '/cms/en/team', '/cms/en/legal'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertMatchesRegularExpression(
                '/<script type="speculationrules">(.*?)<\/script>/s',
                $html,
                "No speculation rules block on {$url}.",
            );
            preg_match('/<script type="speculationrules">(.*?)<\/script>/s', $html, $matches);
            $decoded = json_decode(trim($matches[1]), true);
            $this->assertNotNull($decoded, "Speculation rules JSON is invalid on {$url}: ".json_last_error_msg());
            $this->assertStringContainsString('.no-prefetch, .no-prefetch a', $html);
        }
    }

    /**
     * The CMS share links must also point at the wizard when the pages
     * are rendered from the database (blog index with active posts, and
     * a database page), not just when the captured file is served. The
     * controller also sets a no-store header, which a statically served
     * file never has: it proves the request went through Laravel and
     * the link replacement actually ran.
     */
    public function test_cms_pages_rendered_from_database_share_links_point_at_wizard(): void
    {
        Page::create([
            'slug' => 'duolingo-family-plan-cost-current-discounts-on-duolingo-super-max',
            'type' => 'post',
            'title' => 'Duolingo post',
            'excerpt' => 'Excerpt.',
            'body_html' => '<p>ZzDbPostBody</p>',
            'image_path' => null,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        Page::create([
            'slug' => 'team',
            'type' => 'page',
            'title' => 'ZzTeamTitle',
            'excerpt' => null,
            'body_html' => '<p>ZzDbTeamBody</p>',
            'image_path' => null,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $blog = $this->get('/cms/en')
            ->assertOk()
            ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private')
            ->getContent();
        $this->assertStringContainsString('Duolingo post', $blog, 'Blog index did not render the database post.');
        $this->assertStringContainsString('Excerpt.', $blog, 'Blog index did not render the database post excerpt.');
        $this->assertNoDeadShareLinks($blog, 'CMS blog index from database');
        $this->assertShareLinkPointsAtWizard($blog, 'Share a Subscription', 'CMS blog index from database');

        $team = $this->get('/cms/en/team')
            ->assertOk()
            ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private')
            ->getContent();
        $this->assertStringContainsString('ZzDbTeamBody', $team, 'Team page did not render the database body.');
        $this->assertNoDeadShareLinks($team, 'CMS team page from database');
        $this->assertShareLinkPointsAtWizard($team, 'Share a Subscription', 'CMS team page from database');
    }
}
