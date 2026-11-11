<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Feature;
use App\Models\Offer;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPolish2TemplatesLogoTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    protected function makeBrand(string $name, string $slug, array $overrides = []): Brand
    {
        return Brand::create(array_merge([
            'name' => $name,
            'slug' => $slug,
            'price' => 1.85,
            'currency' => 'GBP',
            'currency_symbol' => '£',
            'is_featured' => true,
            'sort_order' => 0,
            'is_active' => true,
        ], $overrides));
    }

    protected function makeTemplate(Brand $brand, string $name, string $slug): Template
    {
        return Template::create([
            'brand_id' => $brand->id,
            'name' => $name,
            'slug' => $slug,
            'price_with_fees' => 6.99,
            'currency' => 'GBP',
            'sort_order' => 0,
            'is_active' => true,
        ]);
    }

    public function test_templates_index_shows_brand_icon_svg_instead_of_package_emoji(): void
    {
        $brand = $this->makeBrand('Amazon', 'amazon', [
            'icon_svg' => '<svg class="test-amazon-icon" width="24" height="24" viewbox="0 0 24 24"><path d="M1 2"/></svg>',
        ]);
        $this->makeTemplate($brand, 'Amazon Prime Delivery', 'amazon-prime');

        $response = $this->actingAs($this->admin())->get(route('admin.templates.index'));

        $response->assertOk();
        $response->assertSee('Amazon Prime Delivery');
        $response->assertSee('<svg class="test-amazon-icon"', false);
        $response->assertDontSee('📦');
    }

    public function test_templates_index_shows_brand_logo_image_when_logo_path_set(): void
    {
        $brand = $this->makeBrand('Netflix', 'netflix', [
            'logo_path' => '/img/test-netflix-logo.png',
            'icon_svg' => '<svg class="test-netflix-icon"><path d="M1 2"/></svg>',
        ]);
        $this->makeTemplate($brand, 'Netflix extra member', 'netflix-extra-member');

        $response = $this->actingAs($this->admin())->get(route('admin.templates.index'));

        $response->assertOk();
        $response->assertSee('test-netflix-logo.png');
        $response->assertDontSee('test-netflix-icon');
        $response->assertDontSee('📦');
    }

    public function test_templates_index_falls_back_to_package_emoji_when_brand_has_no_logo(): void
    {
        $brand = $this->makeBrand('No Logo Brand', 'no-logo-brand', [
            'logo_path' => null,
            'icon_svg' => null,
        ]);
        $this->makeTemplate($brand, 'Plain Template', 'plain-template');

        $response = $this->actingAs($this->admin())->get(route('admin.templates.index'));

        $response->assertOk();
        $response->assertSee('Plain Template');
        $response->assertSee('📦');
    }

    public function test_brands_index_shows_brand_icon_svg_instead_of_label_emoji(): void
    {
        $this->makeBrand('Spotify', 'spotify', [
            'icon_svg' => '<svg class="test-spotify-icon"><path d="M1 2"/></svg>',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.brands.index'));

        $response->assertOk();
        $response->assertSee('<svg class="test-spotify-icon"', false);
        $response->assertDontSee('🏷️');
    }

    public function test_offers_index_shows_brand_icon_svg_instead_of_ticket_emoji(): void
    {
        $brand = $this->makeBrand('Deezer', 'deezer', [
            'icon_svg' => '<svg class="test-deezer-icon"><path d="M1 2"/></svg>',
        ]);
        Offer::create([
            'brand_id' => $brand->id,
            'title' => 'Deezer Family Offer',
            'price' => 2.50,
            'currency' => 'GBP',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.offers.index'));

        $response->assertOk();
        $response->assertSee('<svg class="test-deezer-icon"', false);
        $response->assertDontSee('🎟️');
    }

    public function test_features_index_shows_feature_icon_instead_of_star_emoji(): void
    {
        Feature::create([
            'title' => 'Legal Service',
            'text' => 'A legal service feature.',
            'icon_path' => '/img/dashboard/features/service-legal.svg',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.features.index'));

        $response->assertOk();
        $response->assertSee('service-legal.svg');
        $response->assertDontSee('⭐');
    }
}
