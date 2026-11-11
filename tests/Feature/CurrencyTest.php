<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Offer;
use App\Models\SiteSetting;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyTest extends TestCase
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

    protected function makeCountry(array $overrides = []): Country
    {
        return Country::create(array_merge([
            'code' => 'GB',
            'name' => 'United Kingdom',
            'fee_flat' => 1.000,
            'fee_percent' => 0.080,
            'currency' => 'GBP',
            'sort_order' => 0,
            'is_active' => true,
        ], $overrides));
    }

    protected function makeCategory(array $overrides = []): Category
    {
        return Category::firstOrCreate(['slug' => $overrides['slug'] ?? 'tv'], array_merge([
            'name' => 'Video',
            'sort_order' => 0,
            'is_active' => true,
        ], $overrides));
    }

    protected function makeBrand(array $overrides = []): Brand
    {
        return Brand::create(array_merge([
            'name' => 'Amazon',
            'slug' => 'amazon',
            'price' => 1.85,
            'full_price' => 9.99,
            'currency' => 'GBP',
            'currency_symbol' => '£',
            'is_featured' => true,
            'sort_order' => 0,
            'is_active' => true,
        ], $overrides));
    }

    protected function makeTemplate(Brand $brand, array $overrides = []): Template
    {
        return Template::create(array_merge([
            'brand_id' => $brand->id,
            'name' => 'Amazon Prime Video - with ads',
            'slug' => 'amazon-prime-video-with-ads',
            'seats' => 6,
            'shareable_seats' => 6,
            'available_seats' => 5,
            'cost_sharing' => 1,
            'credentials_type' => 0,
            'frequency' => 2,
            'price_with_fees' => 1.99,
            'retail_price' => 11.94,
            'currency' => 'GBP',
            'description' => 'Prime Video with ads.',
            'sort_order' => 0,
            'is_active' => true,
        ], $overrides));
    }

    /** Walk the custom (no-template) wizard path up to the currency step. */
    protected function walkToCurrencyStep(Country $country, int $visibility = 1): void
    {
        $category = $this->makeCategory();

        $this->get('/en/create-subscription')->assertOk();
        $this->post('/en/create-subscription/step', ['step' => 'country', 'country_id' => $country->id])
            ->assertRedirect('/en/create-subscription?step=brand');
        $this->post('/en/create-subscription/step', ['step' => 'brand', 'brand_id' => ''])
            ->assertRedirect('/en/create-subscription?step=template');
        $this->post('/en/create-subscription/step', [
            'step' => 'template',
            'name' => 'My Chess Club',
            'description' => 'A shared chess subscription.',
        ])->assertRedirect('/en/create-subscription?step=category');
        $this->post('/en/create-subscription/step', ['step' => 'category', 'category_id' => $category->id])
            ->assertRedirect('/en/create-subscription?step=visibility');
        $this->post('/en/create-subscription/step', ['step' => 'visibility', 'visibility' => $visibility])
            ->assertRedirect('/en/create-subscription?step=currency');
    }

    public function test_seeder_seeds_the_21_capture_currencies_plus_usd(): void
    {
        $this->assertSame(22, Currency::count());

        $usd = Currency::findByCode('USD');
        $this->assertNotNull($usd);
        $this->assertSame(840, $usd->iso_numeric);
        $this->assertSame('$', $usd->symbol);
        $this->assertSame('US Dollar', $usd->name);
        $this->assertTrue($usd->always_offered_in_wizard);

        $gbp = Currency::findByCode('GBP');
        $this->assertNotNull($gbp);
        $this->assertSame(826, $gbp->iso_numeric);
        $this->assertSame('£', $gbp->symbol);
        $this->assertSame('Pound Sterling', $gbp->name);
        $this->assertFalse($gbp->always_offered_in_wizard);

        $eur = Currency::findByCode('EUR');
        $this->assertNotNull($eur);
        $this->assertSame(978, $eur->iso_numeric);
        $this->assertSame('€', $eur->symbol);
        $this->assertTrue($eur->always_offered_in_wizard);

        $chf = Currency::findByCode('CHF');
        $this->assertNotNull($chf);
        $this->assertSame('CHF', $chf->symbol);
        $this->assertSame(756, $chf->iso_numeric);

        $this->assertSame(['ALL', 'AUD', 'CAD', 'CZK', 'DKK', 'GIP', 'HUF', 'ISK', 'MDL', 'NOK', 'RUB', 'SEK', 'CHF', 'MKD', 'GBP', 'BYN', 'RON', 'BAM', 'EUR', 'UAH', 'PLN', 'USD'],
            Currency::ordered()->pluck('code')->all());
    }

    public function test_guest_and_non_admin_cannot_reach_admin_currency_routes(): void
    {
        $this->get(route('admin.currencies.index'))->assertRedirect('/en/userlogin');
        $this->get(route('admin.currencies.create'))->assertRedirect('/en/userlogin');

        $this->actingAs($this->user());
        $this->get(route('admin.currencies.index'))->assertForbidden();
        $this->get(route('admin.currencies.create'))->assertForbidden();
    }

    public function test_admin_currency_crud_happy_path(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.currencies.index'))->assertOk()->assertSee('Pound Sterling')->assertSee('Euro');

        $create = $this->post(route('admin.currencies.store'), [
            'code' => 'NZD',
            'symbol' => '$',
            'name' => 'New Zealand Dollar',
            'iso_numeric' => 554,
            'sort_order' => 21,
            'is_active' => '1',
            'is_available_in_wizard' => '1',
        ]);
        $create->assertRedirect(route('admin.currencies.index'));
        $this->assertDatabaseHas('currencies', ['code' => 'NZD', 'iso_numeric' => 554]);

        $currency = Currency::findByCode('NZD');
        $this->assertNotNull($currency);

        $update = $this->put(route('admin.currencies.update', $currency), [
            'code' => 'NZD',
            'symbol' => 'NZ$',
            'name' => 'New Zealand Dollar',
            'iso_numeric' => 554,
            'sort_order' => 21,
            'is_active' => '1',
            'is_available_in_wizard' => '1',
        ]);
        $update->assertRedirect(route('admin.currencies.edit', $currency));
        $this->assertDatabaseHas('currencies', ['code' => 'NZD', 'symbol' => 'NZ$']);

        $delete = $this->delete(route('admin.currencies.destroy', $currency));
        $delete->assertRedirect(route('admin.currencies.index'));
        $this->assertDatabaseMissing('currencies', ['code' => 'NZD']);
    }

    public function test_admin_dashboard_sidebar_lists_currencies(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk()->assertSee('/en/admin/currencies', false);
    }

    public function test_admin_cannot_create_a_duplicate_currency_code_or_iso(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.currencies.store'), [
            'code' => 'GBP',
            'symbol' => '£',
            'name' => 'Duplicate',
            'iso_numeric' => 999,
        ])->assertSessionHasErrors('code');

        $this->post(route('admin.currencies.store'), [
            'code' => 'XXX',
            'symbol' => 'X',
            'name' => 'Duplicate iso',
            'iso_numeric' => 826,
        ])->assertSessionHasErrors('iso_numeric');
    }

    public function test_wizard_currency_step_offers_country_currency_and_eur_and_usd_from_the_table(): void
    {
        $user = $this->user();
        $country = $this->makeCountry();
        $this->actingAs($user);

        $this->walkToCurrencyStep($country);

        $this->get('/en/create-subscription?step=currency')
            ->assertOk()
            ->assertSee('<span class="text-bold">£</span> GBP', false)
            ->assertSee('Pound Sterling')
            ->assertSee('<span class="text-bold">€</span> EUR', false)
            ->assertSee('Euro')
            ->assertSee('<span class="text-bold">$</span> USD', false)
            ->assertSee('US Dollar');
    }

    public function test_wizard_currency_step_offers_chf_for_switzerland_and_eur_and_usd_for_unknown_currency(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $swiss = $this->makeCountry(['code' => 'CH', 'name' => 'Switzerland', 'currency' => 'CHF']);
        $this->walkToCurrencyStep($swiss);
        $this->get('/en/create-subscription?step=currency')
            ->assertOk()
            ->assertSee('<span class="text-bold">CHF</span> CHF', false)
            ->assertSee('Swiss Franc')
            ->assertSee('<span class="text-bold">€</span> EUR', false)
            ->assertSee('<span class="text-bold">$</span> USD', false)
            ->assertDontSee('£ GBP', false);

        $xpf = $this->makeCountry(['code' => 'PF', 'name' => 'French Polynesia', 'currency' => 'XPF']);
        $this->walkToCurrencyStep($xpf);
        $this->get('/en/create-subscription?step=currency')
            ->assertOk()
            ->assertSee('<span class="text-bold">€</span> EUR', false)
            ->assertSee('<span class="text-bold">$</span> USD', false)
            ->assertDontSee('XPF', false);
    }

    public function test_wizard_currency_step_rejects_a_currency_not_in_the_table(): void
    {
        $user = $this->user();
        $country = $this->makeCountry();
        $this->actingAs($user);

        $this->walkToCurrencyStep($country);

        $this->post('/en/create-subscription/step', ['step' => 'currency', 'currency' => 'XXX'])
            ->assertSessionHasErrors('currency');
    }

    public function test_wizard_currency_step_accepts_usd(): void
    {
        $user = $this->user();
        $country = $this->makeCountry();
        $this->actingAs($user);

        $this->walkToCurrencyStep($country);

        $this->post('/en/create-subscription/step', ['step' => 'currency', 'currency' => 'USD'])
            ->assertRedirect('/en/create-subscription?step=share');
    }

    public function test_template_payload_currency_id_comes_from_the_table(): void
    {
        $brand = $this->makeBrand();
        $template = $this->makeTemplate($brand);
        $user = $this->user();
        $this->actingAs($user);

        $response = $this->get('/en/get-templates?brand_id='.$brand->id.'&country_id=237');

        $response->assertOk();
        $record = $response->json('templates.0');
        $this->assertSame(826, $record['prices'][0]['currency_id']);

        $eurTemplate = $this->makeTemplate($brand, ['slug' => 'eur-plan', 'currency' => 'EUR']);
        $response = $this->get('/en/get-templates?brand_id='.$brand->id.'&country_id=237');
        $records = collect($response->json('templates'))->keyBy('slug');
        $this->assertSame(978, $records['eur-plan']['prices'][0]['currency_id']);
        $this->assertSame(826, $records[$template->slug]['prices'][0]['currency_id']);
    }

    public function test_draft_currency_id_840_now_selects_usd(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $response = $this->post('/en/create-subscription/draft', ['hash' => '', 'currency_id' => 840]);
        $response->assertOk();
        $hash = $response->json('hash');
        $this->assertNotEmpty($hash);

        $check = $this->post('/en/create-subscription/draft/check', ['hash' => $hash]);
        $check->assertOk();
        // USD is seeded now, so a posted 840 selects it.
        $this->assertSame('USD', $check->json('draft.currency'));
        $this->assertSame(840, $check->json('draft.currency_id'));

        $eur = $this->post('/en/create-subscription/draft', ['hash' => $hash, 'currency_id' => 978]);
        $eur->assertOk();
        $check = $this->post('/en/create-subscription/draft/check', ['hash' => $hash]);
        $this->assertSame('EUR', $check->json('draft.currency'));
        $this->assertSame(978, $check->json('draft.currency_id'));
    }

    public function test_brand_price_renders_with_the_symbol_from_the_table(): void
    {
        $this->makeBrand();

        $this->get('/en/brand/amazon')->assertOk()->assertSee('£9.99');
    }

    public function test_changing_a_currency_symbol_in_admin_changes_the_rendered_price(): void
    {
        $brand = $this->makeBrand();
        // An active offer replaces the captured offer rows, so every
        // price on the page is DB-rendered.
        Offer::create([
            'brand_id' => $brand->id,
            'title' => 'Amazon Prime Video - with ads',
            'price' => 1.85,
            'currency' => 'GBP',
            'seller_name' => 'tester',
            'sort_order' => 0,
            'is_active' => true,
        ]);
        $this->get('/en/brand/amazon')->assertOk()->assertSee('£9.99')->assertSee('£1.85');

        $gbp = Currency::findByCode('GBP');
        $this->assertNotNull($gbp);

        $this->actingAs($this->admin());
        $this->put(route('admin.currencies.update', $gbp), [
            'code' => 'GBP',
            'symbol' => 'G',
            'name' => 'Pound Sterling',
            'iso_numeric' => 826,
            'sort_order' => 14,
            'is_active' => '1',
            'is_available_in_wizard' => '1',
            'always_offered_in_wizard' => '0',
        ])->assertRedirect(route('admin.currencies.edit', $gbp));

        $this->assertSame('G', Currency::symbolFor('GBP'));
        // Every rendered price follows the table symbol, including the
        // captured price token in the meta description and tagline.
        $this->get('/en/brand/amazon')->assertOk()->assertSee('G1.85')->assertDontSee('£1.85');
    }

    public function test_default_currency_setting_is_seeded_and_editable(): void
    {
        $this->seed(ContentSeeder::class);

        $setting = SiteSetting::where('key', 'default_currency_code')->first();
        $this->assertNotNull($setting);
        $this->assertSame('GBP', $setting->value);
        $this->assertSame('GBP', Currency::defaultCode());

        $this->actingAs($this->admin());
        $this->put(route('admin.settings.update'), [
            'settings' => ['default_currency_code' => 'EUR'],
        ])->assertRedirect(route('admin.settings.edit'));
        $this->assertSame('EUR', Currency::defaultCode());

        // An unknown code is rejected, so the setting can never point at
        // a currency that has no row.
        $this->put(route('admin.settings.update'), [
            'settings' => ['default_currency_code' => 'XXX'],
        ])->assertSessionHasErrors('settings.default_currency_code');
        $this->assertSame('EUR', Currency::defaultCode());
    }

    public function test_countries_seeder_maps_currencies_from_the_capture_price_rows(): void
    {
        $this->seed(ContentSeeder::class);
        $this->seed(\Database\Seeders\CountriesSeeder::class);

        $byName = Country::query()->pluck('currency', 'name');
        $this->assertSame('GBP', $byName['United Kingdom']);
        $this->assertSame('AUD', $byName['Australia']);
        $this->assertSame('CHF', $byName['Switzerland']);
        $this->assertSame('EUR', $byName['France']);
        $this->assertSame('EUR', $byName['Germany']);
        $this->assertSame('CZK', $byName['Czechia']);
        $this->assertSame('PLN', $byName['Poland']);
        // No price row in the capture: stays on the default currency.
        $this->assertSame('GBP', $byName['French Polynesia']);
        $this->assertSame('GBP', $byName['Wallis & Futuna']);
    }

    public function test_share_step_renders_the_symbol_from_the_table(): void
    {
        $user = $this->user();
        $country = $this->makeCountry();
        $this->actingAs($user);

        $this->walkToCurrencyStep($country);
        $this->post('/en/create-subscription/step', ['step' => 'currency', 'currency' => 'EUR'])
            ->assertRedirect('/en/create-subscription?step=share');

        // The share step formats money like the original's Uo() formatter:
        // at most 2 decimals with trailing zeros dropped, so a zero total
        // shows as €0, not €0.00.
        $this->get('/en/create-subscription?step=share')
            ->assertOk()
            ->assertSee('€0', false)
            ->assertSee('/ month', false);
    }

    public function test_usd_and_country_backfill_migration_repairs_an_existing_database(): void
    {
        // Simulate the pre-fix database: currencies seeded before USD was
        // added, countries all GBP. The seeders only fill empty tables,
        // so only the migration can repair this state.
        Currency::where('code', 'USD')->delete();
        $this->assertNull(Currency::findByCode('USD'));

        $france = $this->makeCountry(['code' => 'FR', 'name' => 'France', 'currency' => 'GBP']);
        $australia = $this->makeCountry(['code' => 'AU', 'name' => 'Australia', 'currency' => 'GBP']);
        $polynesia = $this->makeCountry(['code' => 'PF', 'name' => 'French Polynesia', 'currency' => 'GBP']);

        $migration = require database_path('migrations/2026_11_11_000013_seed_usd_and_backfill_country_currencies.php');
        $migration->up();

        $usd = Currency::findByCode('USD');
        $this->assertNotNull($usd);
        $this->assertSame(840, $usd->iso_numeric);
        $this->assertSame('$', $usd->symbol);
        $this->assertTrue($usd->always_offered_in_wizard);
        $this->assertSame('EUR', $france->fresh()->currency);
        $this->assertSame('AUD', $australia->fresh()->currency);
        $this->assertSame('GBP', $polynesia->fresh()->currency);

        // Idempotent: running it again changes nothing.
        $migration->up();
        $this->assertSame(22, Currency::count());
        $this->assertSame('EUR', $france->fresh()->currency);

        $migration->down();
        $this->assertNull(Currency::findByCode('USD'));
        $this->assertSame('GBP', $france->fresh()->currency);
        $this->assertSame('GBP', $australia->fresh()->currency);
    }
}
