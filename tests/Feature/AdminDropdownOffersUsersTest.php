<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminOffersController;
use App\Models\Brand;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AdminDropdownOffersUsersTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    protected function makeBrand(array $overrides = []): Brand
    {
        return Brand::create(array_merge([
            'name' => 'Amazon',
            'slug' => 'amazon',
            'price' => 1.85,
            'currency' => 'GBP',
            'currency_symbol' => '£',
            'is_featured' => true,
            'sort_order' => 0,
            'is_active' => true,
        ], $overrides));
    }

    protected function makeOffer(Brand $brand, array $overrides = []): Offer
    {
        return Offer::create(array_merge([
            'brand_id' => $brand->id,
            'user_id' => null,
            'status' => 'active',
            'title' => 'Amazon Prime Video - with ads',
            'price' => 4.23,
            'currency' => 'GBP',
            'seats_total' => null,
            'seats_available' => 2,
            'seller_name' => 'socheatalim',
            'sort_order' => 0,
            'is_active' => true,
        ], $overrides));
    }

    protected function makeCountry(array $overrides = []): Country
    {
        return Country::create(array_merge([
            'code' => 'GB',
            'name' => 'United Kingdom',
            'fee_flat' => 1,
            'fee_percent' => 0.08,
            'currency' => 'GBP',
            'sort_order' => 0,
            'is_active' => true,
        ], $overrides));
    }

    protected function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
            'role' => 'user',
            'language' => 'en',
        ], $overrides));
    }

    protected function userPayload(User $user, array $overrides = []): array
    {
        return array_filter(array_merge([
            'first_name' => $user->first_name ?? 'Test',
            'last_name' => $user->last_name ?? 'User',
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'language' => $user->language ?: 'en',
            'country' => $user->country,
        ], $overrides), fn ($value) => $value !== null);
    }

    protected function offerPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Test Offer Plan',
            'price' => '3.50',
            'currency' => 'GBP',
            'seats_total' => 5,
            'seats_available' => 2,
            'seller_name' => 'Test Seller',
            'sort_order' => 1,
            'is_active' => '1',
        ], $overrides);
    }

    public function test_offer_form_dropdowns_list_database_brands_and_currencies(): void
    {
        $brand = $this->makeBrand();
        $other = $this->makeBrand(['name' => 'Netflix', 'slug' => 'netflix']);

        $this->actingAs($this->admin());

        $response = $this->get(route('admin.offers.create'));

        $response->assertOk();
        $response->assertSee('value="'.$brand->id.'"', false);
        $response->assertSee('Amazon', false);
        $response->assertSee('value="'.$other->id.'"', false);
        $response->assertSee('Netflix', false);
        // Currencies come from the seeded currencies table (22 rows).
        $response->assertSee('value="GBP" selected', false);
        $response->assertSee('value="EUR"', false);
        $response->assertSee('value="USD"', false);
    }

    public function test_offer_saved_from_dropdown_persists_currency_and_brand(): void
    {
        $brand = $this->makeBrand();
        $other = $this->makeBrand(['name' => 'Netflix', 'slug' => 'netflix']);

        $this->actingAs($this->admin());

        $response = $this->post(route('admin.offers.store'), $this->offerPayload([
            'brand_id' => $other->id,
            'title' => 'Netflix Premium',
            'price' => '5.50',
            'currency' => 'EUR',
        ]));

        $response->assertRedirect(route('admin.offers.index'));
        $this->assertDatabaseHas('offers', [
            'title' => 'Netflix Premium',
            'brand_id' => $other->id,
            'currency' => 'EUR',
            'price' => 5.50,
        ]);
        $this->assertDatabaseMissing('offers', ['brand_id' => $brand->id, 'title' => 'Netflix Premium']);
    }

    public function test_offer_update_from_dropdown_persists_new_currency_and_brand(): void
    {
        $brand = $this->makeBrand();
        $other = $this->makeBrand(['name' => 'Netflix', 'slug' => 'netflix']);
        $offer = $this->makeOffer($brand);

        $this->actingAs($this->admin());

        $response = $this->put(route('admin.offers.update', $offer), $this->offerPayload([
            'brand_id' => $other->id,
            'currency' => 'USD',
            'price' => '6.00',
        ]));

        $response->assertRedirect(route('admin.offers.edit', $offer));
        $this->assertDatabaseHas('offers', [
            'id' => $offer->id,
            'brand_id' => $other->id,
            'currency' => 'USD',
            'price' => 6.00,
        ]);
    }

    public function test_offer_store_rejects_currency_not_in_table(): void
    {
        $brand = $this->makeBrand();

        $this->actingAs($this->admin());

        $response = $this->post(route('admin.offers.store'), $this->offerPayload([
            'brand_id' => $brand->id,
            'currency' => 'ZZZ',
        ]));

        $response->assertSessionHasErrors('currency');
        $this->assertDatabaseMissing('offers', ['title' => 'Test Offer Plan']);
    }

    public function test_offer_edit_keeps_stored_currency_not_in_table_as_selected_option(): void
    {
        $brand = $this->makeBrand();
        $offer = $this->makeOffer($brand, ['currency' => 'XXX']);

        $this->actingAs($this->admin());

        $response = $this->get(route('admin.offers.edit', $offer));

        $response->assertOk();
        $response->assertSee('value="XXX" selected', false);
    }

    public function test_offer_edit_keeps_stored_brand_not_in_table_as_selected_option(): void
    {
        $brand = $this->makeBrand();
        $offer = $this->makeOffer($brand);
        $other = $this->makeBrand(['name' => 'Netflix', 'slug' => 'netflix']);

        $this->actingAs($this->admin());
        $this->get(route('admin.offers.edit', $offer))->assertOk();

        // A dangling brand id can only come from legacy data (the FK
        // cascades on delete), so set it in memory: the stored value is
        // not in the brands list handed to the form.
        $offer->brand_id = 999999;

        $html = view('admin.offers.form', [
            'offer' => $offer,
            'brands' => Brand::where('id', $other->id)->get(),
            'currencies' => Currency::ordered()->get(),
        ])->render();

        $this->assertStringContainsString('value="999999" selected', $html);
    }

    public function test_offer_rules_accept_stored_brand_not_in_table_but_reject_other_unknown_brands(): void
    {
        $brand = $this->makeBrand();
        $offer = $this->makeOffer($brand);
        $offer->brand_id = 999999;

        $method = new \ReflectionMethod(AdminOffersController::class, 'rules');
        $rules = $method->invoke(new AdminOffersController, $offer);

        $passes = Validator::make([
            'brand_id' => 999999,
            'title' => 'Test Offer Plan',
            'price' => '3.50',
            'currency' => 'GBP',
        ], $rules);
        $this->assertTrue($passes->passes());

        $fails = Validator::make([
            'brand_id' => 123456,
            'title' => 'Test Offer Plan',
            'price' => '3.50',
            'currency' => 'GBP',
        ], $rules);
        $this->assertTrue($fails->fails());
        $this->assertArrayHasKey('brand_id', $fails->errors()->toArray());
    }

    public function test_user_edit_dropdowns_list_database_countries_languages_and_roles(): void
    {
        $this->makeCountry();
        $this->makeCountry(['code' => 'FR', 'name' => 'France', 'currency' => 'EUR', 'fee_flat' => 0, 'fee_percent' => 0]);
        $target = $this->makeUser();

        $this->actingAs($this->admin());

        $response = $this->get(route('admin.users.edit', $target));

        $response->assertOk();
        $response->assertSee('value="GB"', false);
        $response->assertSee('United Kingdom', false);
        $response->assertSee('value="FR"', false);
        $response->assertSee('France', false);
        $response->assertSee('value="en"', false);
        $response->assertSee('value="fr"', false);
        $response->assertSee('value="user" selected', false);
        $response->assertSee('value="admin"', false);
        $response->assertDontSee('<input class="input-sharesub" id="country"', false);
        $response->assertDontSee('<input class="input-sharesub" id="language"', false);
    }

    public function test_user_update_from_dropdowns_persists_role_country_and_language(): void
    {
        $this->makeCountry();
        $this->makeCountry(['code' => 'FR', 'name' => 'France', 'currency' => 'EUR', 'fee_flat' => 0, 'fee_percent' => 0]);
        $target = $this->makeUser(['role' => 'user', 'country' => 'GB', 'language' => 'en']);

        $this->actingAs($this->admin());

        $response = $this->put(route('admin.users.update', $target), $this->userPayload($target, [
            'role' => 'admin',
            'country' => 'FR',
            'language' => 'fr',
        ]));

        $response->assertRedirect(route('admin.users.edit', $target));
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'role' => 'admin',
            'country' => 'FR',
            'language' => 'fr',
        ]);
    }

    public function test_user_role_select_still_works_admin_back_to_user(): void
    {
        $target = $this->makeUser(['role' => 'admin']);

        $this->actingAs($this->admin());

        $response = $this->put(route('admin.users.update', $target), $this->userPayload($target, [
            'role' => 'user',
        ]));

        $response->assertRedirect(route('admin.users.edit', $target));
        $this->assertDatabaseHas('users', ['id' => $target->id, 'role' => 'user']);
    }

    public function test_user_update_rejects_country_not_in_table(): void
    {
        $this->makeCountry();
        $target = $this->makeUser(['country' => 'GB']);

        $this->actingAs($this->admin());

        $response = $this->put(route('admin.users.update', $target), $this->userPayload($target, [
            'country' => 'US',
        ]));

        $response->assertSessionHasErrors('country');
        $this->assertDatabaseHas('users', ['id' => $target->id, 'country' => 'GB']);
    }

    public function test_user_edit_keeps_stored_country_and_language_not_in_sets_as_selected_options(): void
    {
        $target = $this->makeUser(['country' => 'ZZ', 'language' => 'xx']);

        $this->actingAs($this->admin());

        $this->get(route('admin.users.edit', $target))
            ->assertOk()
            ->assertSee('value="ZZ" selected', false)
            ->assertSee('value="xx" selected', false);

        $response = $this->put(route('admin.users.update', $target), $this->userPayload($target));

        $response->assertRedirect(route('admin.users.edit', $target));
        $this->assertDatabaseHas('users', ['id' => $target->id, 'country' => 'ZZ', 'language' => 'xx']);
    }
}
