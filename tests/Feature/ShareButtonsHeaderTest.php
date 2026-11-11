<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Share buttons and the auth aware header. A share call to action
 * points at the wizard for everyone: the wizard route is behind the
 * auth guard, so a guest clicking one lands on the login page and a
 * logged in user lands on the wizard. The header shows the auth
 * buttons only to guests, the "My account" menu only to a logged in
 * normal user, and an Admin panel link (never a user panel link) to
 * an admin.
 */
class ShareButtonsHeaderTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
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

    private function navHtml(string $html): string
    {
        preg_match('/<nav\b[^>]*sharesub-navbar[^>]*>.*?<\/nav>/s', $html, $match);

        return $match[0] ?? '';
    }

    /**
     * @return array<int, array{href: string, text: string}>
     */
    private function anchors(string $html): array
    {
        preg_match_all(
            '/<a\b[^>]*href="([^"]*)"[^>]*>(.*?)<\/a>/is',
            $html,
            $anchors,
            PREG_SET_ORDER,
        );

        $found = [];
        foreach ($anchors as $anchor) {
            $found[] = [
                'href' => $anchor[1],
                'text' => trim((string) preg_replace('/\s+/', ' ', strip_tags($anchor[2]))),
            ];
        }

        return $found;
    }

    private function assertAnchorPointsAt(string $html, string $label, string $href, string $page): void
    {
        $found = false;
        foreach ($this->anchors($html) as $anchor) {
            if ($anchor['text'] === $label) {
                $found = true;
                $this->assertSame($href, $anchor['href'], "On {$page} the '{$label}' link points at {$anchor['href']}.");
            }
        }

        $this->assertTrue($found, "On {$page} no '{$label}' link was found at all.");
    }

    public function test_guest_header_shows_auth_buttons_and_share_points_at_wizard(): void
    {
        $html = $this->get('/en')->assertOk()->getContent();
        $nav = $this->navHtml($html);

        $this->assertNotSame('', $nav, 'No site header on the guest homepage.');
        $this->assertAnchorPointsAt($nav, 'Share my subscription', '/en/create-subscription', 'guest homepage nav');
        $this->assertAnchorPointsAt($nav, 'Register', '/en/register', 'guest homepage nav');
        $this->assertAnchorPointsAt($nav, 'Login', '/en/userlogin', 'guest homepage nav');
        $this->assertStringNotContainsString('My account', $nav, 'Guest header shows a My account menu.');
        $this->assertStringNotContainsString('navbarDropdown', $nav, 'Guest header carries the logged in dropdown.');
        // The mobile slide-out menu shows the auth buttons to a guest.
        $this->assertStringContainsString('<span>Register</span>', $nav, 'Guest mobile menu has no Register button.');
        $this->assertStringContainsString('<span>Login</span>', $nav, 'Guest mobile menu has no Login button.');
    }

    public function test_guest_share_click_lands_on_login_and_login_returns_to_wizard(): void
    {
        $user = $this->user();

        // The share button href is the wizard; the auth guard sends a
        // guest to the login page and remembers where they were going.
        $this->get('/en/create-subscription')->assertRedirect('/en/userlogin');

        $login = $this->get('/en/userlogin')->assertOk()->getContent();
        $this->assertStringContainsString(
            '<input type="hidden" name="return" value="/en/create-subscription">',
            $login,
            'Login page does not carry the wizard as its return URL.',
        );

        $this->post('/en/userlogin', [
            'email' => $user->email,
            'password' => 'password',
            'return' => '/en/create-subscription',
        ])->assertRedirect('/en/create-subscription');

        $this->assertAuthenticatedAs($user);
        $wizard = $this->get('/en/create-subscription')->assertOk()->getContent();
        $this->assertStringContainsString('Create your shared subscription', $wizard);
    }

    public function test_guest_create_my_account_buttons_point_at_register(): void
    {
        $html = $this->get('/en')->assertOk()->getContent();

        $createAccount = array_values(array_filter(
            $this->anchors($html),
            static fn (array $anchor): bool => $anchor['text'] === 'Create my account',
        ));

        // Hero see-all button and the second call to action button.
        $this->assertCount(2, $createAccount, 'Expected two Create my account buttons on the guest homepage.');

        foreach ($createAccount as $anchor) {
            $this->assertSame(
                '/en/register',
                $anchor['href'],
                "The 'Create my account' button points at {$anchor['href']} instead of the registration page.",
            );
        }
    }

    public function test_logged_in_user_header_has_my_account_menu_and_no_auth_buttons(): void
    {
        $this->makeAmazonBrand();
        $this->actingAs($this->user());

        foreach (['/en', '/en/subscriptions', '/en/subscriptions/house', '/en/brand/amazon'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $nav = $this->navHtml($html);

            $this->assertNotSame('', $nav, "No site header on {$url} for a logged in user.");
            $this->assertStringContainsString('My account', $nav, "No My account menu in the header on {$url}.");
            $this->assertStringContainsString('navbarDropdown', $nav, "No My account dropdown in the header on {$url}.");
            $this->assertStringContainsString('href="/en/dashboard"', $nav, "No Dashboard link in the My account menu on {$url}.");
            $this->assertStringContainsString('href="/en/my-shared-subscriptions"', $nav, "No Shared subscriptions link in the My account menu on {$url}.");
            $this->assertStringContainsString('href="/en/my-chat/subscriptions"', $nav, "No Chat link in the My account menu on {$url}.");
            $this->assertAnchorPointsAt($nav, 'Share my subscription', '/en/create-subscription', "logged in header on {$url}");
            $this->assertStringNotContainsString('>Register</a>', $nav, "Register button still in the header on {$url}.");
            $this->assertStringNotContainsString('>Login</a>', $nav, "Login button still in the header on {$url}.");
            $this->assertStringNotContainsString('href="/en/register"', $nav, "Register link still in the header on {$url}.");
            $this->assertStringNotContainsString('href="/en/userlogin"', $nav, "Login link still in the header on {$url}.");
            // The mobile slide-out menu must swap too: a logged in user
            // sees a My account item there, never Register or Login.
            $this->assertStringContainsString('href="/en/dashboard" class="menu-links-item"', $nav, "No My account item in the mobile menu on {$url}.");
            $this->assertStringNotContainsString('<span>Register</span>', $nav, "Register button still in the mobile menu on {$url}.");
            $this->assertStringNotContainsString('<span>Login</span>', $nav, "Login button still in the mobile menu on {$url}.");
            $this->assertStringNotContainsString('Create my account', $html, "Create my account still on {$url} for a logged in user.");
            $this->assertStringContainsString('id="logout-form"', $html, "No logout form on {$url} for a logged in user.");
        }
    }

    public function test_logged_in_user_share_buttons_point_at_wizard(): void
    {
        $this->actingAs($this->user());

        $home = $this->get('/en')->assertOk()->getContent();
        $this->assertAnchorPointsAt($home, 'Share my subscription', '/en/create-subscription', 'logged in homepage');
        $this->assertAnchorPointsAt($home, 'Propose a subscription', '/en/create-subscription', 'logged in homepage');
        $this->assertAnchorPointsAt($home, 'Share', '/en/create-subscription', 'logged in homepage share card');
        $this->assertAnchorPointsAt($home, "I'm sharing my subscription", '/en/create-subscription', 'logged in homepage call to action');

        $dashboard = $this->get('/en/dashboard')->assertOk()->getContent();
        $this->assertAnchorPointsAt($dashboard, 'Share', '/en/create-subscription', 'dashboard share card');
        $this->assertAnchorPointsAt($dashboard, 'Propose a subscription', '/en/create-subscription', 'dashboard');

        $shared = $this->get('/en/my-shared-subscriptions')->assertOk()->getContent();
        $this->assertAnchorPointsAt($shared, 'Create a shared Subscription', '/en/create-subscription', 'my shared subscriptions');

        $chat = $this->get('/en/my-chat/subscriptions')->assertOk()->getContent();
        $this->assertAnchorPointsAt($chat, 'Create subscription', '/en/create-subscription', 'chat empty state');
        $this->assertAnchorPointsAt($chat, 'Propose a subscription', '/en/create-subscription', 'chat page');
    }

    public function test_admin_header_has_admin_panel_link_and_no_user_panel_links(): void
    {
        $this->makeAmazonBrand();
        $this->actingAs($this->admin());

        foreach (['/en', '/en/subscriptions', '/en/brand/amazon'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $nav = $this->navHtml($html);

            $this->assertNotSame('', $nav, "No site header on {$url} for an admin.");
            $this->assertAnchorPointsAt($nav, 'Admin panel', '/en/admin', "admin header on {$url}");
            $this->assertAnchorPointsAt($nav, 'Share my subscription', '/en/create-subscription', "admin header on {$url}");
            $this->assertStringNotContainsString('My account', $nav, "My account user menu still in the admin header on {$url}.");
            $this->assertStringNotContainsString('navbarDropdown', $nav, "User dropdown still in the admin header on {$url}.");
            // The mobile slide-out menu gets the Admin panel item too.
            $this->assertStringContainsString('href="/en/admin" class="menu-links-item"', $nav, "No Admin panel item in the mobile menu on {$url}.");
            $this->assertStringNotContainsString('>Register</a>', $nav, "Register button still in the admin header on {$url}.");
            $this->assertStringNotContainsString('>Login</a>', $nav, "Login button still in the admin header on {$url}.");

            foreach ([
                '/en/dashboard',
                '/en/my-shared-subscriptions',
                '/en/joined-subscriptions',
                '/en/waiting-list',
                '/en/my-wallet',
                '/en/my-cards',
                '/en/my-account',
                '/en/my-settings',
                '/en/my-chat',
            ] as $userLink) {
                $this->assertStringNotContainsString(
                    $userLink,
                    $nav,
                    "User panel link {$userLink} still in the admin header on {$url}.",
                );
            }

            $this->assertStringNotContainsString('Create my account', $html, "Create my account still on {$url} for an admin.");
        }
    }
}
