<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\FooterLinkGroup;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminModulesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    protected function moduleIndexRoutes(): array
    {
        return [
            'brands' => 'admin.brands.index',
            'categories' => 'admin.categories.index',
            'settings' => 'admin.settings.edit',
            'how-it-works' => 'admin.how-it-works.index',
            'faqs' => 'admin.faqs.index',
            'testimonials' => 'admin.testimonials.index',
            'press' => 'admin.press.index',
            'footer-groups' => 'admin.footer-groups.index',
            'features' => 'admin.features.index',
        ];
    }

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guests_are_redirected_to_login_on_every_module_index(): void
    {
        foreach ($this->moduleIndexRoutes() as $name => $route) {
            $this->get(route($route))->assertRedirect('/en/userlogin');
        }
    }

    public function test_normal_users_get_forbidden_on_every_module_index(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        foreach ($this->moduleIndexRoutes() as $name => $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }
    }

    public function test_admins_can_open_every_module_index(): void
    {
        $this->actingAs($this->admin());

        foreach ($this->moduleIndexRoutes() as $name => $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_admin_dashboard_sidebar_lists_every_module(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk();
        foreach (['/en/admin/brands', '/en/admin/categories', '/en/admin/content/settings', '/en/admin/how-it-works', '/en/admin/faqs', '/en/admin/testimonials', '/en/admin/press', '/en/admin/footer-groups', '/en/admin/features'] as $url) {
            $response->assertSee($url, false);
        }
    }

    public function test_brand_crud_happy_path(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $create = $this->post(route('admin.brands.store'), [
            'name' => 'Test Brand',
            'slug' => 'test-brand',
            'price' => '4.95',
            'currency' => 'GBP',
            'currency_symbol' => '£',
            'discount_percent' => 25,
            'short_description' => 'A test subscription brand.',
            'tagline' => 'Share it and save.',
            'is_featured' => '1',
            'is_active' => '1',
            'sort_order' => 1,
            'logo' => UploadedFile::fake()->createWithContent('logo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAwMCAO+ip1sAAAAASUVORK5CYII=')),
        ]);
        $create->assertRedirect(route('admin.brands.index'));
        $this->assertDatabaseHas('brands', ['slug' => 'test-brand', 'price' => 4.95]);

        $brand = Brand::where('slug', 'test-brand')->firstOrFail();
        $this->assertNotNull($brand->logo_path);
        Storage::disk('public')->assertExists($brand->logo_path);

        $this->get(route('admin.brands.index'))->assertOk()->assertSee('Test Brand');

        $update = $this->put(route('admin.brands.update', $brand), [
            'name' => 'Test Brand',
            'slug' => 'test-brand',
            'price' => '5.95',
            'currency' => 'GBP',
            'currency_symbol' => '£',
            'is_featured' => '1',
            'is_active' => '1',
            'sort_order' => 1,
        ]);
        $update->assertRedirect(route('admin.brands.edit', $brand));
        $this->assertDatabaseHas('brands', ['slug' => 'test-brand', 'price' => 5.95]);

        $delete = $this->delete(route('admin.brands.destroy', $brand));
        $delete->assertRedirect(route('admin.brands.index'));
        $this->assertDatabaseMissing('brands', ['slug' => 'test-brand']);
        Storage::disk('public')->assertMissing($brand->logo_path);
    }

    public function test_site_settings_update_saves_keys(): void
    {
        $this->actingAs($this->admin());

        $response = $this->put(route('admin.settings.update'), [
            'settings' => [
                'hero_highlight' => 'Share',
                'hero_text' => 'the cost of your',
                'hero_underline' => 'subscriptions',
                'footer_note' => 'Sharesub helps you manage payments.',
            ],
        ]);

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertDatabaseHas('site_settings', ['key' => 'hero_highlight', 'value' => 'Share']);
        $this->assertDatabaseHas('site_settings', ['key' => 'footer_note', 'value' => 'Sharesub helps you manage payments.']);
        $this->assertSame('Share', SiteSetting::where('key', 'hero_highlight')->first()->value);
    }

    public function test_footer_link_crud_is_nested_under_its_group(): void
    {
        $this->actingAs($this->admin());
        $group = FooterLinkGroup::create(['title' => 'Company', 'sort_order' => 0]);

        $create = $this->post(route('admin.footer-links.store', $group), [
            'label' => 'About us',
            'url' => '/en/about',
            'sort_order' => 0,
            'is_active' => '1',
        ]);
        $create->assertRedirect(route('admin.footer-groups.show', $group));
        $this->assertDatabaseHas('footer_links', ['label' => 'About us', 'footer_link_group_id' => $group->id]);

        $this->get(route('admin.footer-groups.show', $group))->assertOk()->assertSee('About us');

        $link = $group->footerLinks()->firstOrFail();
        $this->put(route('admin.footer-links.update', [$group, $link]), [
            'label' => 'About CutCost',
            'url' => '/en/about',
            'sort_order' => 0,
            'is_active' => '1',
        ])->assertRedirect(route('admin.footer-links.edit', [$group, $link]));
        $this->assertDatabaseHas('footer_links', ['label' => 'About CutCost']);

        $this->delete(route('admin.footer-links.destroy', [$group, $link]))
            ->assertRedirect(route('admin.footer-groups.show', $group));
        $this->assertDatabaseMissing('footer_links', ['label' => 'About CutCost']);
    }
}
