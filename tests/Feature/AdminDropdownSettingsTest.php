<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Country;
use App\Models\Currency;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDropdownSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_settings_page_renders_currency_select_not_text_input(): void
    {
        $this->actingAs($this->admin());

        $response = $this->get(route('admin.settings.edit'));

        $response->assertOk();
        $response->assertSee('<select class="input-sharesub" id="settings-default_currency_code" name="settings[default_currency_code]">', false);
        $response->assertDontSee('<input class="input-sharesub" id="settings-default_currency_code"', false);
    }

    public function test_settings_currency_dropdown_lists_db_currencies(): void
    {
        $this->actingAs($this->admin());

        $response = $this->get(route('admin.settings.edit'));

        $response->assertOk();
        foreach (Currency::ordered()->get() as $currency) {
            $response->assertSee('<option value="'.$currency->code.'"', false);
        }
        $response->assertSee('GBP', false);
        $response->assertSee('USD', false);
        $response->assertSee('EUR', false);
    }

    public function test_settings_currency_dropdown_shows_stored_value_as_selected(): void
    {
        SiteSetting::updateOrCreate(['key' => 'default_currency_code'], ['value' => 'EUR']);

        $this->actingAs($this->admin());

        $response = $this->get(route('admin.settings.edit'));

        $response->assertOk();
        $response->assertSee('<option value="EUR" selected', false);
    }

    public function test_settings_currency_dropdown_keeps_unknown_stored_value_as_selected_option(): void
    {
        SiteSetting::updateOrCreate(['key' => 'default_currency_code'], ['value' => 'ZZZ']);

        $this->actingAs($this->admin());

        $response = $this->get(route('admin.settings.edit'));

        $response->assertOk();
        $response->assertSee('<option value="ZZZ" selected', false);
    }

    public function test_saving_currency_from_dropdown_persists_it(): void
    {
        $this->actingAs($this->admin());

        $response = $this->put(route('admin.settings.update'), [
            'settings' => ['default_currency_code' => 'USD'],
        ]);

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertDatabaseHas('site_settings', ['key' => 'default_currency_code', 'value' => 'USD']);
        $this->assertSame('USD', Currency::defaultCode());
    }

    public function test_saving_unknown_currency_is_still_rejected(): void
    {
        $this->actingAs($this->admin());

        $response = $this->put(route('admin.settings.update'), [
            'settings' => ['default_currency_code' => 'ZZZ'],
        ]);

        $response->assertSessionHasErrors('settings.default_currency_code');
        $this->assertDatabaseMissing('site_settings', ['key' => 'default_currency_code', 'value' => 'ZZZ']);
    }

    public function test_settings_page_renders_locale_and_brand_dropdowns(): void
    {
        SiteSetting::updateOrCreate(['key' => 'locales_json'], ['value' => json_encode([
            ['code' => 'en', 'label' => 'English', 'url' => '/en/index.html'],
            ['code' => 'fr', 'label' => 'Francais', 'url' => '/fr/index.html'],
        ])]);
        Brand::create([
            'name' => 'Netflix',
            'slug' => 'netflix',
            'price' => 15.49,
            'currency' => 'GBP',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin());

        $response = $this->get(route('admin.settings.edit'));

        $response->assertOk();
        $response->assertSee('<select class="input-sharesub" id="settings-default_locale" name="settings[default_locale]">', false);
        $response->assertSee('<option value="fr"', false);
        $response->assertSee('<select class="input-sharesub" id="settings-fallback_locale" name="settings[fallback_locale]">', false);
        $response->assertSee('<select class="input-sharesub" id="settings-calculator_default_brand_slug" name="settings[calculator_default_brand_slug]">', false);
        $response->assertSee('<option value="netflix"', false);
    }

    public function test_country_form_renders_currency_dropdown_from_db(): void
    {
        $this->actingAs($this->admin());

        $response = $this->get(route('admin.countries.create'));

        $response->assertOk();
        $response->assertSee('<select class="input-sharesub" id="currency" name="currency">', false);
        foreach (Currency::ordered()->get() as $currency) {
            $response->assertSee('<option value="'.$currency->code.'"', false);
        }
    }

    public function test_country_currency_saved_from_dropdown_persists(): void
    {
        $this->actingAs($this->admin());

        $response = $this->post(route('admin.countries.store'), [
            'code' => 'FR',
            'name' => 'France',
            'fee_flat' => 0,
            'fee_percent' => 0,
            'currency' => 'EUR',
            'sort_order' => 0,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.countries.index'));
        $this->assertDatabaseHas('countries', ['code' => 'FR', 'currency' => 'EUR']);
        $this->assertSame('EUR', Country::where('code', 'FR')->first()->currency);
    }

    public function test_country_edit_shows_stored_currency_as_selected(): void
    {
        $country = Country::create([
            'code' => 'DE',
            'name' => 'Germany',
            'fee_flat' => 0,
            'fee_percent' => 0,
            'currency' => 'EUR',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin());

        $response = $this->get(route('admin.countries.edit', $country));

        $response->assertOk();
        $response->assertSee('<option value="EUR" selected', false);
    }
}
