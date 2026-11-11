<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Country;
use App\Models\Faq;
use App\Models\HowItWorksStep;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentPhase2Test extends TestCase
{
    use RefreshDatabase;

    private function setSetting(string $key, string $value): void
    {
        SiteSetting::create(['key' => $key, 'value' => $value]);
    }

    private function makeCategory(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'name' => 'Music',
            'slug' => 'music',
            'sort_order' => 0,
            'is_active' => true,
        ], $overrides));
    }

    private function makeBrand(array $overrides = []): Brand
    {
        return Brand::create(array_merge([
            'name' => 'ZzTest',
            'slug' => 'zztest',
            'price' => 3.21,
            'currency' => 'GBP',
            'currency_symbol' => '£',
            'background' => '#FF8900',
            'is_featured' => true,
            'sort_order' => 0,
            'is_active' => true,
        ], $overrides));
    }

    private function user(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_cta_blocks_render_from_settings(): void
    {
        $this->setSetting('cta1_title', 'ZzCtaOneTitle');
        $this->setSetting('cta1_text', 'ZzCtaOneText');
        $this->setSetting('cta1_button_label', 'ZzCtaOneButton');
        $this->setSetting('cta2_title', 'ZzCtaTwoTitle');
        $this->setSetting('cta2_text', 'ZzCtaTwoText');
        $this->setSetting('cta2_button_label', 'ZzCtaTwoButton');

        $response = $this->get('/en');

        $response->assertStatus(200);
        $response->assertSee('ZzCtaOneTitle');
        $response->assertSee('ZzCtaOneText');
        $response->assertSee('ZzCtaOneButton');
        $response->assertSee('ZzCtaTwoTitle');
        $response->assertSee('ZzCtaTwoText');
        $response->assertSee('ZzCtaTwoButton');
    }

    public function test_both_how_it_works_sets_render_with_the_right_audience(): void
    {
        HowItWorksStep::create([
            'title' => 'ZzShareStep',
            'text' => 'ZzShareStepText',
            'audience' => 'share',
            'sort_order' => 0,
            'is_active' => true,
        ]);
        HowItWorksStep::create([
            'title' => 'ZzSeatStep',
            'text' => 'ZzSeatStepText',
            'audience' => 'seat',
            'sort_order' => 0,
            'is_active' => true,
        ]);
        $this->setSetting('how_share_label', 'ZzShareLabel');
        $this->setSetting('how_seat_label', 'ZzSeatLabel');

        $response = $this->get('/en');

        $response->assertStatus(200);
        $response->assertSee('ZzShareStep');
        $response->assertSee('ZzShareStepText');
        $response->assertSee('ZzSeatStep');
        $response->assertSee('ZzSeatStepText');
        $response->assertSee('ZzShareLabel');
        $response->assertSee('ZzSeatLabel');
    }

    public function test_category_description_renders_on_catalogue_and_house_pages(): void
    {
        $music = $this->makeCategory(['description' => 'ZzMusicDesc']);
        $this->makeBrand(['category_id' => $music->id]);

        $catalogue = $this->get('/en/subscriptions');
        $catalogue->assertStatus(200);
        $catalogue->assertSee('ZzMusicDesc');

        $house = $this->makeCategory(['name' => 'House', 'slug' => 'house', 'description' => 'ZzHouseDesc', 'sort_order' => 1]);
        $this->makeBrand(['name' => 'ZzHouseBrand', 'slug' => 'zzhousebrand', 'category_id' => $house->id]);

        $housePage = $this->get('/en/subscriptions/house');
        $housePage->assertStatus(200);
        $housePage->assertSee('ZzHouseDesc');
    }

    public function test_catalogue_count_renders_from_the_setting(): void
    {
        $this->setSetting('catalogue_services_count', '999');

        $response = $this->get('/en');

        $response->assertStatus(200);
        $response->assertSee('total-templates="999"', false);
        $response->assertSee("var FIXED_COUNT = '999'", false);
        $response->assertDontSee('total-templates="770"', false);
    }

    public function test_footer_tagline_and_copyright_render(): void
    {
        $this->setSetting('footer_tagline', 'ZzTagline {heart} here');
        $this->setSetting('footer_copyright', 'ZzCopyright 2030');

        $response = $this->get('/en');

        $response->assertStatus(200);
        $response->assertSee('ZzTagline');
        $response->assertSee('ZzCopyright 2030');
        $response->assertDontSee('© 2026 Sharesub');
    }

    public function test_json_ld_faq_matches_db_faqs(): void
    {
        Faq::create([
            'question' => 'ZzFaqQuestion',
            'answer' => 'ZzFaqAnswer',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $response = $this->get('/en');

        $response->assertStatus(200);
        $response->assertSee('"name": "ZzFaqQuestion"', false);
        $response->assertSee('ZzFaqAnswer');
    }

    public function test_hero_search_json_contains_a_seeded_country(): void
    {
        Country::create([
            'code' => 'ZZ',
            'name' => 'ZzTestland',
            'fee_flat' => '0.000',
            'fee_percent' => '0.000',
            'currency' => 'GBP',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $response = $this->get('/en');

        $response->assertStatus(200);
        $response->assertSee('ZzTestland');
        $response->assertSee('zztestland', false);
    }

    public function test_guest_cannot_reach_admin_countries_routes(): void
    {
        $this->get(route('admin.countries.index'))->assertRedirect('/en/userlogin');
        $this->post(route('admin.countries.store'), ['code' => 'ZZ', 'name' => 'ZzTestland'])->assertRedirect('/en/userlogin');
        $this->actingAs($this->user())->get(route('admin.countries.index'))->assertForbidden();
    }

    public function test_admin_countries_crud_happy_path(): void
    {
        $this->actingAs($this->admin());

        $create = $this->post(route('admin.countries.store'), [
            'code' => 'zz',
            'name' => 'ZzTestland',
            'fee_flat' => '1.000',
            'fee_percent' => '0.080',
            'currency' => 'GBP',
            'postal_regex' => '^[0-9]{4}$',
            'postal_example' => '1000',
            'sort_order' => 1,
            'is_active' => '1',
        ]);
        $create->assertRedirect(route('admin.countries.index'));
        $this->assertDatabaseHas('countries', ['code' => 'ZZ', 'name' => 'ZzTestland']);

        $country = Country::where('code', 'ZZ')->firstOrFail();

        $this->get(route('admin.countries.index'))->assertOk()->assertSee('ZzTestland');

        $update = $this->put(route('admin.countries.update', $country), [
            'code' => 'ZZ',
            'name' => 'ZzTestlandRenamed',
            'fee_flat' => '2.000',
            'fee_percent' => '0.100',
            'currency' => 'GBP',
            'sort_order' => 1,
            'is_active' => '1',
        ]);
        $update->assertRedirect(route('admin.countries.edit', $country));
        $this->assertDatabaseHas('countries', ['code' => 'ZZ', 'name' => 'ZzTestlandRenamed']);

        $delete = $this->delete(route('admin.countries.destroy', $country));
        $delete->assertRedirect(route('admin.countries.index'));
        $this->assertDatabaseMissing('countries', ['code' => 'ZZ']);
    }

    public function test_dashboard_copy_renders_from_settings(): void
    {
        $this->setSetting('dashboard_cta_title', 'ZzDashboardCta');
        $this->setSetting('dashboard_checklist_1_title', 'ZzChecklistOne');
        $this->setSetting('dashboard_checklist_1_text', 'ZzChecklistOneText');
        $this->setSetting('shared_empty_text', 'ZzSharedEmpty');

        $this->actingAs($this->user());

        $dashboard = $this->get('/en/dashboard');
        $dashboard->assertStatus(200);
        $dashboard->assertSee('ZzDashboardCta');
        $dashboard->assertSee('ZzChecklistOne');
        $dashboard->assertSee('ZzChecklistOneText');

        $shared = $this->get('/en/my-shared-subscriptions');
        $shared->assertStatus(200);
        $shared->assertSee('ZzSharedEmpty');
    }
}
