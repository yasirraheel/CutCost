<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\SiteSetting;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplatesTest extends TestCase
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

    /** @return array<int, array{0: string, 1: string}> */
    protected function heroBrands(): array
    {
        return [
            ['Amazon', 'amazon'],
            ['Crunchyroll', 'crunchyroll'],
            ['Deezer', 'deezer'],
            ['Disney', 'disney'],
            ['Netflix', 'netflix'],
            ['Nord VPN', 'nord-vpn'],
            ['Microsoft', 'microsoft'],
            ['Pure VPN', 'pure-vpn'],
            ['Spotify', 'spotify'],
            ['Youtube', 'youtube'],
            ['Paramount', 'paramount'],
            ['DAZN', 'dazn'],
            ['HBO', 'hbo'],
        ];
    }

    public function test_template_seeder_extracts_thirteen_calculator_templates_plus_eight_amazon_templates(): void
    {
        foreach ($this->heroBrands() as [$name, $slug]) {
            $this->makeBrand($name, $slug);
        }

        $this->seed(TemplateSeeder::class);

        $this->assertSame(20, Template::count());
        $this->assertDatabaseHas('templates', ['slug' => 'nordpass', 'brand_id' => Brand::where('slug', 'nord-vpn')->firstOrFail()->id]);
        $this->assertDatabaseHas('templates', ['slug' => 'office-365-family', 'brand_id' => Brand::where('slug', 'microsoft')->firstOrFail()->id]);
        $this->assertDatabaseHas('templates', ['slug' => 'purevpn', 'brand_id' => Brand::where('slug', 'pure-vpn')->firstOrFail()->id]);

        $audible = Template::where('slug', 'audible')->firstOrFail();
        $this->assertSame(1.85, (float) $audible->price_with_fees);
        $this->assertSame(7.99, (float) $audible->retail_price);
        $this->assertSame('GBP', $audible->currency);
        $this->assertSame(10, $audible->seats);
        $this->assertSame(10, $audible->shareable_seats);
        $this->assertSame(9, $audible->available_seats);
        $this->assertSame('Amazon', $audible->brand->name);

        $netflix = Template::where('slug', 'netflix-extra-member-without-ads')->firstOrFail();
        $this->assertSame(11.05, (float) $netflix->price_with_fees);
        $this->assertSame(38.42, (float) $netflix->retail_price);
        $this->assertStringNotContainsString("\u{2014}", (string) $netflix->helper_create);
        $this->assertStringNotContainsString("\u{2013}", (string) $netflix->helper_join);

        $this->seed(TemplateSeeder::class);
        $this->assertSame(20, Template::count());
    }

    public function test_amazon_has_all_eight_captured_templates(): void
    {
        foreach ($this->heroBrands() as [$name, $slug]) {
            $this->makeBrand($name, $slug);
        }

        $this->seed(TemplateSeeder::class);

        $amazon = Brand::where('slug', 'amazon')->firstOrFail();
        $templates = Template::where('brand_id', $amazon->id)->orderBy('sort_order')->orderBy('id')->get();

        $this->assertSame(8, $templates->count());
        $this->assertSame([
            'Amazon Prime Delivery',
            'Amazon Music Unlimited Family Plan',
            'Amazon Prime Video - with ads',
            'Audible',
            'Amazon Prime Video - without Ads',
            'Amazon Prime + Paramount Plus',
            'Amazon Prime Video Pass Warner',
            'Amazon Prime Video Pass Paramount Plus',
        ], $templates->pluck('name')->all());

        // name => [seats, shareable_seats, available_seats, credentials_type, retail_price]
        $expected = [
            'Amazon Prime Delivery' => [2, 2, 1, 5, 6.99],
            'Amazon Music Unlimited Family Plan' => [6, 6, 5, 3, 17.99],
            'Amazon Prime Video - with ads' => [3, 2, 2, 5, 8.99],
            'Audible' => [10, 10, 9, 0, 7.99],
            'Amazon Prime Video - without Ads' => [3, 2, 2, 0, 10.98],
            'Amazon Prime + Paramount Plus' => [2, 1, 1, 2, 15.98],
            'Amazon Prime Video Pass Warner' => [3, 3, 2, 5, 14.47],
            'Amazon Prime Video Pass Paramount Plus' => [3, 3, 2, 5, 12.76],
        ];

        foreach ($templates as $template) {
            [$seats, $shareable, $available, $credentialsType, $price] = $expected[$template->name];
            $this->assertSame($seats, $template->seats);
            $this->assertSame($shareable, $template->shareable_seats);
            $this->assertSame($available, $template->available_seats);
            $this->assertSame($credentialsType, $template->credentials_type);
            $this->assertSame(2, $template->frequency);
            $this->assertSame(1, $template->cost_sharing);
            $this->assertSame($price, (float) $template->retail_price);
            $this->assertSame('GBP', $template->currency);
        }

        // The calculator's shared seat price is untouched: the homepage
        // calculator still shows Amazon from 1.85.
        $this->assertSame(1.85, (float) $templates->firstWhere('slug', 'audible')->price_with_fees);

        // The Prime Delivery helper text carries the tutorial link with
        // a real apostrophe, not a leftover capture escape.
        $helper = (string) $templates->firstWhere('slug', 'amazon-prime')->helper_create;
        $this->assertStringContainsString(
            'https://www.sharesub.com/cms/en/share-your-amazon-prime-account-with-a-loved-one/',
            $helper
        );
        $this->assertStringContainsString("\u{2019}", $helper);
        $this->assertStringNotContainsString('\u2019', $helper);

        // Re-seeding changes nothing.
        $this->seed(TemplateSeeder::class);
        $this->assertSame(8, Template::where('brand_id', $amazon->id)->count());
        $this->assertSame(20, Template::count());
    }

    public function test_homepage_calculator_uses_database_templates(): void
    {
        $this->makeBrand('Amazon', 'amazon');
        $this->makeBrand('Netflix', 'netflix');

        $this->seed(TemplateSeeder::class);

        SiteSetting::create(['key' => 'calculator_default_brand_slug', 'value' => 'netflix']);
        SiteSetting::create(['key' => 'calculator_avg_yearly_savings', 'value' => '777']);

        $template = Template::where('slug', 'netflix-extra-member-without-ads')->firstOrFail();

        $response = $this->get('/en');

        $response->assertStatus(200);
        $response->assertSee('Netflix extra member without ads');
        $response->assertSee('netflix-extra-member-without-ads');
        $response->assertSee('\/en\/brand\/netflix?template=netflix-extra-member-without-ads');
        $response->assertSee(':default-brand=\''.$template->id.'\'', false);
        $response->assertSee(':avg-yearly-savings=\'777\'', false);
        $response->assertDontSee(':default-brand=\'122\'', false);
    }

    public function test_homepage_calculator_falls_back_to_captured_json_without_templates(): void
    {
        $this->makeBrand('Amazon', 'amazon');

        $response = $this->get('/en');

        $response->assertStatus(200);
        $response->assertSee(':default-brand=\'122\'', false);
        $response->assertSee(':avg-yearly-savings=\'405\'', false);
    }

    public function test_filter_tags_render_from_templates(): void
    {
        $brand = $this->makeBrand('Amazon', 'amazon');

        Template::create([
            'brand_id' => $brand->id,
            'name' => 'Test Plan Zulu',
            'slug' => 'test-plan-zulu',
            'price_with_fees' => 2.50,
            'currency' => 'GBP',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $response = $this->get('/en/brand/amazon');

        $response->assertStatus(200);
        $response->assertSee('Test Plan Zulu');
        $response->assertDontSee('Amazon Prime Delivery');
    }

    public function test_guest_cannot_reach_admin_templates_routes(): void
    {
        $this->get(route('admin.templates.index'))->assertRedirect('/en/userlogin');
        $this->get(route('admin.templates.create'))->assertRedirect('/en/userlogin');
        $this->post(route('admin.templates.store'), [])->assertRedirect('/en/userlogin');
    }

    public function test_admin_template_crud_happy_path(): void
    {
        $brand = $this->makeBrand('Amazon', 'amazon');
        $this->actingAs($this->admin());

        $create = $this->post(route('admin.templates.store'), [
            'brand_id' => $brand->id,
            'name' => 'Test Template Plan',
            'slug' => 'test-template-plan',
            'seats' => 6,
            'shareable_seats' => 5,
            'available_seats' => 4,
            'price_with_fees' => '3.50',
            'retail_price' => '12.99',
            'currency' => 'GBP',
            'description' => 'A test template.',
            'title_seo' => 'Test Template SEO',
            'sort_order' => 1,
            'is_active' => '1',
        ]);
        $create->assertRedirect(route('admin.templates.index'));
        $this->assertDatabaseHas('templates', ['slug' => 'test-template-plan', 'price_with_fees' => 3.50]);

        $this->get(route('admin.templates.index'))->assertOk()->assertSee('Test Template Plan');

        $template = Template::where('slug', 'test-template-plan')->firstOrFail();

        $update = $this->put(route('admin.templates.update', $template), [
            'brand_id' => $brand->id,
            'name' => 'Test Template Plan',
            'slug' => 'test-template-plan',
            'seats' => 6,
            'shareable_seats' => 5,
            'available_seats' => 3,
            'price_with_fees' => '3.75',
            'retail_price' => '12.99',
            'currency' => 'GBP',
            'description' => 'A test template.',
            'title_seo' => 'Test Template SEO',
            'sort_order' => 1,
            'is_active' => '1',
        ]);
        $update->assertRedirect(route('admin.templates.edit', $template));
        $this->assertDatabaseHas('templates', ['slug' => 'test-template-plan', 'price_with_fees' => 3.75]);

        $delete = $this->delete(route('admin.templates.destroy', $template));
        $delete->assertRedirect(route('admin.templates.index'));
        $this->assertDatabaseMissing('templates', ['slug' => 'test-template-plan']);
    }
}
