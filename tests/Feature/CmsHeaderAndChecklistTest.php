<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Coordinator merge fixes: the CMS header "My account" link follows
 * the auth state (a guest keeps /en/userlogin, a logged in user goes
 * to their dashboard, an admin goes to the admin panel), and the
 * dashboard checklist Complete buttons go to the flow each item
 * names (Join to the catalogue, Share to the wizard, Add a payment
 * method to the cards page).
 */
class CmsHeaderAndChecklistTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['role' => 'user', 'first_name' => 'Demo']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'first_name' => 'Admin']);
    }

    public function test_cms_my_account_link_points_at_login_for_guests(): void
    {
        $content = $this->get('/cms/en/legal/tos')->assertOk()->getContent();

        $this->assertStringContainsString('<a href="/en/userlogin">My account</a>', $content);
    }

    public function test_cms_my_account_link_points_at_dashboard_for_logged_in_users(): void
    {
        $content = $this->actingAs($this->user())->get('/cms/en/legal/tos')->assertOk()->getContent();

        $this->assertStringContainsString('<a href="/en/dashboard">My account</a>', $content);
        $this->assertStringNotContainsString('<a href="/en/userlogin">My account</a>', $content);
    }

    public function test_cms_my_account_link_becomes_admin_panel_for_admins(): void
    {
        $content = $this->actingAs($this->admin())->get('/cms/en/legal/tos')->assertOk()->getContent();

        $this->assertStringContainsString('<a href="/en/admin">Admin panel</a>', $content);
        $this->assertStringNotContainsString('<a href="/en/userlogin">My account</a>', $content);
    }

    public function test_dashboard_checklist_complete_buttons_go_to_their_flows(): void
    {
        $content = $this->actingAs($this->user())->get('/en/dashboard')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/Join my first subscription.*?href="\/en\/subscriptions".*?>Complete<\/a>/s',
            $content
        );
        $this->assertMatchesRegularExpression(
            '/Share my first subscription.*?href="\/en\/create-subscription".*?>Complete<\/a>/s',
            $content
        );
        $this->assertMatchesRegularExpression(
            '/Add a payment method.*?href="\/en\/my-cards".*?>Complete<\/a>/s',
            $content
        );
    }
}
