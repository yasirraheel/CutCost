<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDropdownBrandsTemplatesTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    protected function makeCategory(string $name, string $slug): Category
    {
        return Category::create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'sort_order' => 0,
        ]);
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

    protected function makeTemplate(Brand $brand, string $name, string $slug, array $overrides = []): Template
    {
        return Template::create(array_merge([
            'brand_id' => $brand->id,
            'name' => $name,
            'slug' => $slug,
            'price_with_fees' => 1.85,
            'currency' => 'GBP',
            'sort_order' => 0,
            'is_active' => true,
        ], $overrides));
    }

    // Currencies are reference data seeded by the base TestCase
    // (CurrenciesSeeder, 22 rows: 21 capture + USD), so tests use the
    // seeded GBP, EUR and USD rows directly.

    public function test_brand_create_page_lists_database_currencies_and_categories_in_dropdowns(): void
    {
        $this->makeCategory('Streaming', 'streaming');
        $this->makeCategory('Music', 'music');

        $response = $this->actingAs($this->admin())->get(route('admin.brands.create'));

        $response->assertOk();
        $response->assertSee('name="currency"', false);
        $response->assertSee('GBP', false);
        $response->assertSee('Euro', false);
        $response->assertSee('US Dollar', false);
        $response->assertSee('name="category_id"', false);
        $response->assertSee('Streaming', false);
        $response->assertSee('Music', false);
    }

    public function test_brand_currency_saved_from_dropdown_persists(): void
    {
        $category = $this->makeCategory('Streaming', 'streaming');

        $response = $this->actingAs($this->admin())->post(route('admin.brands.store'), [
            'name' => 'Netflix',
            'slug' => 'netflix',
            'price' => 11.05,
            'currency' => 'EUR',
            'category_id' => $category->id,
        ]);

        $response->assertRedirect(route('admin.brands.index'));
        $this->assertDatabaseHas('brands', ['slug' => 'netflix', 'currency' => 'EUR', 'category_id' => $category->id]);
    }

    public function test_brand_currency_can_be_changed_from_dropdown(): void
    {
        $brand = $this->makeBrand('Netflix', 'netflix', ['currency' => 'GBP']);

        $response = $this->actingAs($this->admin())->put(route('admin.brands.update', $brand), [
            'name' => 'Netflix',
            'slug' => 'netflix',
            'price' => 11.05,
            'currency' => 'USD',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'currency' => 'USD']);
    }

    public function test_brand_edit_page_keeps_stored_currency_not_in_currencies_table(): void
    {
        $brand = $this->makeBrand('Netflix', 'netflix', ['currency' => 'XXX']);

        $response = $this->actingAs($this->admin())->get(route('admin.brands.edit', $brand));

        $response->assertOk();
        $response->assertSee('XXX (stored value, not in currencies)', false);
        $response->assertSee('value="XXX" selected', false);
    }

    public function test_brand_edit_page_keeps_deleted_category_selected(): void
    {
        $category = $this->makeCategory('Streaming', 'streaming');
        $brand = $this->makeBrand('Netflix', 'netflix', ['category_id' => $category->id]);

        // A normal category delete nulls the brand link, so render the form
        // with the category gone from the list, as a legacy or imported
        // row with a stale category id would have it.
        $this->actingAs($this->admin())->get(route('admin.brands.edit', $brand))->assertOk();

        $html = view('admin.brands.form', [
            'brand' => $brand,
            'categories' => collect(),
            'currencies' => Currency::ordered()->get(),
        ])->render();

        $this->assertStringContainsString("Category #{$category->id} (no longer in categories)", $html);
        $this->assertStringContainsString("value=\"{$category->id}\" selected", $html);
    }

    public function test_template_create_page_lists_database_brands_currencies_and_set_dropdowns(): void
    {
        $this->makeBrand('Netflix', 'netflix');

        $response = $this->actingAs($this->admin())->get(route('admin.templates.create'));

        $response->assertOk();
        $response->assertSee('name="brand_id"', false);
        $response->assertSee('Netflix', false);
        $response->assertSee('name="currency"', false);
        $response->assertSee('Euro', false);
        $response->assertSee('name="credentials_type"', false);
        $response->assertSee('No credentials needed', false);
        $response->assertSee('Login and password', false);
        $response->assertSee('Invite link', false);
        $response->assertSee('name="frequency"', false);
        $response->assertSee('/month', false);
        $response->assertSee('/year', false);
        $response->assertSee('name="cost_sharing"', false);
        $response->assertSee('Split across all seats', false);
        $response->assertSee('Subscribers pay the full price', false);
    }

    public function test_template_dropdown_values_saved_on_create(): void
    {
        $brand = $this->makeBrand('Netflix', 'netflix');

        $response = $this->actingAs($this->admin())->post(route('admin.templates.store'), [
            'brand_id' => $brand->id,
            'name' => 'Netflix Extra Member',
            'slug' => 'netflix-extra-member',
            'price_with_fees' => 11.05,
            'currency' => 'EUR',
            'credentials_type' => 3,
            'frequency' => 4,
            'cost_sharing' => 3,
        ]);

        $response->assertRedirect(route('admin.templates.index'));
        $this->assertDatabaseHas('templates', [
            'slug' => 'netflix-extra-member',
            'currency' => 'EUR',
            'credentials_type' => 3,
            'frequency' => 4,
            'cost_sharing' => 3,
        ]);
    }

    public function test_template_dropdown_values_default_when_not_posted(): void
    {
        $brand = $this->makeBrand('Netflix', 'netflix');

        $this->actingAs($this->admin())->post(route('admin.templates.store'), [
            'brand_id' => $brand->id,
            'name' => 'Netflix Standard',
            'slug' => 'netflix-standard',
            'price_with_fees' => 5.99,
            'currency' => 'GBP',
        ]);

        $this->assertDatabaseHas('templates', [
            'slug' => 'netflix-standard',
            'credentials_type' => 0,
            'frequency' => 2,
            'cost_sharing' => 1,
        ]);
    }

    public function test_template_dropdown_values_can_be_changed_on_update(): void
    {
        $brand = $this->makeBrand('Netflix', 'netflix');
        $template = $this->makeTemplate($brand, 'Netflix Standard', 'netflix-standard', [
            'credentials_type' => 0,
            'frequency' => 2,
            'cost_sharing' => 1,
        ]);

        $response = $this->actingAs($this->admin())->put(route('admin.templates.update', $template), [
            'brand_id' => $brand->id,
            'name' => 'Netflix Standard',
            'slug' => 'netflix-standard',
            'price_with_fees' => 5.99,
            'currency' => 'EUR',
            'credentials_type' => 2,
            'frequency' => 1,
            'cost_sharing' => 2,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('templates', [
            'id' => $template->id,
            'currency' => 'EUR',
            'credentials_type' => 2,
            'frequency' => 1,
            'cost_sharing' => 2,
        ]);
    }

    public function test_template_edit_page_keeps_unknown_credentials_type_selected_and_saves_it(): void
    {
        $brand = $this->makeBrand('Amazon', 'amazon');
        $template = $this->makeTemplate($brand, 'Amazon Prime Delivery', 'amazon-prime', [
            'credentials_type' => 5,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.templates.edit', $template));

        $response->assertOk();
        $response->assertSee('5 (stored value, type not defined in app)', false);
        $response->assertSee('value="5" selected', false);

        $update = $this->actingAs($this->admin())->put(route('admin.templates.update', $template), [
            'brand_id' => $brand->id,
            'name' => 'Amazon Prime Delivery',
            'slug' => 'amazon-prime',
            'price_with_fees' => 1.85,
            'currency' => 'GBP',
            'credentials_type' => 5,
            'frequency' => 2,
            'cost_sharing' => 1,
        ]);

        $update->assertRedirect();
        $this->assertDatabaseHas('templates', ['id' => $template->id, 'credentials_type' => 5]);
    }

    public function test_template_edit_page_keeps_deleted_brand_selected(): void
    {
        $brand = $this->makeBrand('Netflix', 'netflix');
        $template = $this->makeTemplate($brand, 'Netflix Standard', 'netflix-standard');

        // A normal brand delete cascades to its templates, so render the
        // form with the brand gone from the list, as a legacy or imported
        // row with a stale brand id would have it.
        $this->actingAs($this->admin())->get(route('admin.templates.edit', $template))->assertOk();

        $html = view('admin.templates.form', [
            'template' => $template,
            'brands' => collect(),
            'currencies' => Currency::ordered()->get(),
            'credentialTypes' => [0 => 'No credentials needed'],
            'frequencies' => [2 => '/month'],
            'costSharingOptions' => [1 => 'Split across all seats'],
        ])->render();

        $this->assertStringContainsString("Brand #{$brand->id} (no longer in brands)", $html);
        $this->assertStringContainsString("value=\"{$brand->id}\" selected", $html);
    }

    public function test_template_rejects_credentials_type_outside_the_known_set(): void
    {
        $brand = $this->makeBrand('Netflix', 'netflix');

        $response = $this->actingAs($this->admin())->post(route('admin.templates.store'), [
            'brand_id' => $brand->id,
            'name' => 'Netflix Standard',
            'slug' => 'netflix-standard',
            'price_with_fees' => 5.99,
            'currency' => 'GBP',
            'credentials_type' => 9,
        ]);

        $response->assertSessionHasErrors('credentials_type');
        $this->assertDatabaseMissing('templates', ['slug' => 'netflix-standard']);
    }
}
