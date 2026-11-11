<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserPanelSeparationTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    protected function user(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    /** @return array<string, string> User panel URL to the admin section it redirects to. */
    protected function userPanelRedirects(): array
    {
        return [
            '/en/dashboard' => '/en/admin',
            '/en/my-shared-subscriptions' => '/en/admin/shared-subscriptions',
            '/en/joined-subscriptions' => '/en/admin/joined-subscriptions',
            '/en/waiting-list' => '/en/admin/waiting-list',
            '/en/my-account' => '/en/admin/account',
            '/en/my-settings' => '/en/admin/settings',
            '/en/my-account/identity' => '/en/admin/account/identity',
            '/en/my-documents' => '/en/admin/account/identity',
            '/en/my-wallet' => '/en/admin/wallet',
            '/en/my-cards' => '/en/admin/cards',
            '/en/my-bank-account' => '/en/admin',
            '/en/my-chat/subscriptions' => '/en/admin/chat',
            '/en/create-subscription' => '/en/admin',
        ];
    }

    public function test_admin_is_redirected_to_admin_panel_from_every_user_panel_page(): void
    {
        $this->actingAs($this->admin());

        foreach ($this->userPanelRedirects() as $url => $destination) {
            $this->get($url)->assertRedirect($destination);
        }
    }

    public function test_admin_is_redirected_from_user_panel_posts_too(): void
    {
        $this->actingAs($this->admin());

        $this->post('/en/my-settings/security', [])->assertRedirect(route('admin.dashboard'));
        $this->post('/en/my-account', [])->assertRedirect(route('admin.dashboard'));
    }

    public function test_normal_user_can_still_reach_every_user_panel_page(): void
    {
        $this->actingAs($this->user());

        foreach (array_keys($this->userPanelRedirects()) as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_admin_can_still_manage_the_admin_panel(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.offers.index'))->assertOk();
        $this->get(route('admin.users'))->assertOk();
    }

    public function test_admin_can_still_log_out(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('logout'))->assertRedirect('/en');
        $this->assertGuest();
    }

    public function test_guest_still_goes_to_login_from_user_panel(): void
    {
        $this->get('/en/dashboard')->assertRedirect('/en/userlogin');
        $this->get('/en/my-shared-subscriptions')->assertRedirect('/en/userlogin');
    }
}
