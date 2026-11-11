<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Country;
use App\Models\Offer;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => 'user'], $overrides));
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
        return Category::create(array_merge([
            'name' => 'Video',
            'slug' => 'tv',
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

    protected function makeSeededOffer(Brand $brand, array $overrides = []): Offer
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

    public function test_guest_is_redirected_to_login_on_the_wizard(): void
    {
        $response = $this->get('/en/create-subscription');

        $response->assertRedirect('/en/userlogin');
    }

    public function test_guest_is_redirected_to_login_on_the_json_endpoints(): void
    {
        $this->get('/en/get-brands')->assertRedirect('/en/userlogin');
        $this->get('/en/get-templates')->assertRedirect('/en/userlogin');
    }

    public function test_wizard_walks_the_template_path_and_publishes(): void
    {
        $user = $this->user();
        $country = $this->makeCountry();
        $brand = $this->makeBrand();
        $template = $this->makeTemplate($brand);

        $this->actingAs($user);

        $this->get('/en/create-subscription')->assertOk()->assertSee('Country');

        $this->post('/en/create-subscription/step', ['step' => 'country', 'country_id' => $country->id])
            ->assertRedirect('/en/create-subscription?step=brand');

        $this->post('/en/create-subscription/step', ['step' => 'brand', 'brand_id' => $brand->id])
            ->assertRedirect('/en/create-subscription?step=template');

        $this->post('/en/create-subscription/step', ['step' => 'template', 'template_id' => $template->id])
            ->assertRedirect('/en/create-subscription?step=visibility');

        $this->post('/en/create-subscription/step', ['step' => 'visibility', 'visibility' => 1])
            ->assertRedirect('/en/create-subscription?step=share');

        $this->post('/en/create-subscription/step', ['step' => 'share', 'friends' => 2])
            ->assertRedirect('/en/create-subscription?step=additional');

        $response = $this->post('/en/create-subscription', [
            'secured_mode' => 'shared',
            'secured_description_shared' => 'I share the login with all members by email.',
            'accept_tos' => 1,
        ]);

        $response->assertRedirect('/en/my-shared-subscriptions');

        $offer = Offer::where('user_id', $user->id)->first();
        $this->assertNotNull($offer);
        $this->assertSame('active', $offer->status);
        $this->assertTrue($offer->is_active);
        $this->assertSame($brand->id, (int) $offer->brand_id);
        $this->assertSame($template->id, (int) $offer->template_id);
        $this->assertSame('Amazon Prime Video - with ads', $offer->name);
        $this->assertSame(2, (int) $offer->friends);
        $this->assertSame(6, (int) $offer->total_seats);
        $this->assertSame('1.99', (string) $offer->price);
        $this->assertSame(['I share the login with all members by email.'], $offer->secured_description);
    }

    public function test_wizard_walks_the_custom_path_with_position_and_currency(): void
    {
        $user = $this->user();
        $country = $this->makeCountry();
        $category = $this->makeCategory();

        $this->actingAs($user);
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

        $this->post('/en/create-subscription/step', ['step' => 'visibility', 'visibility' => 2])
            ->assertRedirect('/en/create-subscription?step=position');

        $this->post('/en/create-subscription/step', ['step' => 'position', 'lat' => 48.85, 'lng' => 2.35])
            ->assertRedirect('/en/create-subscription?step=currency');

        $this->post('/en/create-subscription/step', ['step' => 'currency', 'currency' => 'EUR'])
            ->assertRedirect('/en/create-subscription?step=share');

        $this->post('/en/create-subscription/step', [
            'step' => 'share',
            'total_price' => 15.99,
            'friends' => 2,
            'total_seats' => 3,
        ])->assertRedirect('/en/create-subscription?step=additional');

        $this->post('/en/create-subscription', [
            'secured_mode' => 'shared',
            'secured_description_shared' => 'I send the invite link to each member.',
            'accept_tos' => 1,
        ])->assertRedirect('/en/my-shared-subscriptions');

        $offer = Offer::where('user_id', $user->id)->first();
        $this->assertNotNull($offer);
        $this->assertSame('active', $offer->status);
        $this->assertNull($offer->brand_id);
        $this->assertTrue($offer->custom_price);
        $this->assertSame($category->id, (int) $offer->category_id);
        $this->assertSame(2, (int) $offer->visibility);
        $this->assertSame('EUR', $offer->currency);
        $this->assertSame('5.33', (string) $offer->price);
        $this->assertEqualsWithDelta(48.85, (float) $offer->lat, 0.0000001);
        $this->assertEqualsWithDelta(2.35, (float) $offer->lng, 0.0000001);
    }

    public function test_private_visibility_needs_confirmation(): void
    {
        $user = $this->user();
        $country = $this->makeCountry();

        $this->actingAs($user);
        $this->get('/en/create-subscription')->assertOk();
        $this->post('/en/create-subscription/step', ['step' => 'country', 'country_id' => $country->id]);

        $response = $this->post('/en/create-subscription/step', ['step' => 'visibility', 'visibility' => 0]);
        $response->assertSessionHasErrors('confirm_private');

        $this->post('/en/create-subscription/step', ['step' => 'visibility', 'visibility' => 0, 'confirm_private' => 1])
            ->assertRedirect('/en/create-subscription?step=currency');
    }

    public function test_draft_json_round_trip_and_resume(): void
    {
        $user = $this->user();
        $country = $this->makeCountry();
        $brand = $this->makeBrand();
        $template = $this->makeTemplate($brand);

        $this->actingAs($user);

        $response = $this->post('/en/create-subscription/draft', [
            'hash' => '',
            'brand_id' => $brand->id,
            'template_id' => $template->id,
            'country_id' => $country->id,
            'currency_id' => 826,
            'name' => 'Amazon Prime Video - with ads',
            'description' => 'Prime Video with ads.',
            'share' => 1,
            'friends' => 3,
            'total_seats' => 6,
            'price' => 5.97,
            'custom_price' => 0,
            'frequency' => 2,
            'lat' => '',
            'long' => '',
            'email' => '',
            'public' => 1,
        ]);

        $response->assertOk()->assertJson(['done' => true]);
        $hash = $response->json('hash');
        $this->assertNotEmpty($hash);

        $offer = Offer::where('hash', $hash)->first();
        $this->assertNotNull($offer);
        $this->assertSame('draft', $offer->status);
        $this->assertSame('1.99', (string) $offer->price);
        $this->assertSame('11.94', (string) $offer->total_price);

        $check = $this->post('/en/create-subscription/draft/check', ['hash' => $hash]);
        $check->assertOk()->assertJson([
            'done' => true,
            'draft' => [
                'brand_id' => $brand->id,
                'template_id' => $template->id,
                'country_id' => $country->id,
                'seats' => 3,
                'public' => 1,
            ],
        ]);

        $other = $this->user(['email' => 'other@example.com']);
        $this->actingAs($other);
        $this->post('/en/create-subscription/draft/check', ['hash' => $hash])
            ->assertOk()->assertJson(['done' => true, 'draft' => null]);

        $this->actingAs($user);
        $this->get('/en/create-subscription?draft='.$hash.'&step=template')->assertOk()->assertSee('Amazon Prime Video - with ads');

        $this->post('/en/create-subscription', [
            'secured_mode' => 'shared',
            'secured_description_shared' => 'I share the login with all members by email.',
            'accept_tos' => 1,
        ])->assertRedirect('/en/my-shared-subscriptions');

        $this->assertSame('active', $offer->fresh()->status);
    }

    public function test_get_brands_returns_db_backed_json(): void
    {
        $this->makeBrand();
        $this->makeBrand(['name' => 'Netflix', 'slug' => 'netflix', 'sort_order' => 1]);

        $response = $this->actingAs($this->user())->get('/en/get-brands?search=net');

        $response->assertOk()->assertJson(['done' => true]);
        $brands = $response->json('brands');
        $this->assertCount(1, $brands);
        $this->assertSame('Netflix', $brands[0]['name']);
        $this->assertSame(['id', 'name', 'description_seo', 'title_seo', 'html_reinsurance'], array_keys($brands[0]));
        $this->assertNull($brands[0]['html_reinsurance']);
    }

    public function test_get_templates_returns_db_backed_json(): void
    {
        $brand = $this->makeBrand();
        $template = $this->makeTemplate($brand);

        $response = $this->actingAs($this->user())->get('/en/get-templates?brand_id='.$brand->id.'&country_id=237');

        $response->assertOk()->assertJson(['done' => true]);
        $templates = $response->json('templates');
        $this->assertCount(1, $templates);
        $record = $templates[0];
        $this->assertSame($template->id, $record['id']);
        $this->assertSame(1, $record['cost_sharing']);
        $this->assertSame(0, $record['credentials_type']);
        $this->assertSame(2, $record['frequency']);
        $this->assertSame(1, $record['status']);
        $this->assertSame(237, $record['prices'][0]['country_id']);
        $this->assertSame('11.94', $record['prices'][0]['price']);
        $this->assertSame(826, $record['prices'][0]['currency_id']);
        $this->assertSame(7, $record['prices'][0]['visibility']);
    }

    public function test_check_brand_allows_active_brands_only(): void
    {
        $brand = $this->makeBrand();
        $inactive = $this->makeBrand(['name' => 'Old', 'slug' => 'old', 'is_active' => false]);

        $this->actingAs($this->user());

        $this->post('/en/create-subscription/check-brand', ['brand_id' => $brand->id, 'return_brand' => true])
            ->assertOk()->assertJson(['allow' => true, 'brand' => ['name' => 'Amazon']]);

        $this->post('/en/create-subscription/check-brand', ['brand_id' => $inactive->id])
            ->assertOk()->assertJson(['allow' => false]);
    }

    public function test_credentials_are_required_by_template_type_and_stored_encrypted(): void
    {
        $user = $this->user();
        $country = $this->makeCountry();
        $brand = $this->makeBrand();
        $this->makeTemplate($brand, ['credentials_type' => 2]);

        $this->actingAs($user);
        $this->get('/en/create-subscription')->assertOk();
        $this->post('/en/create-subscription/step', ['step' => 'country', 'country_id' => $country->id]);
        $this->post('/en/create-subscription/step', ['step' => 'brand', 'brand_id' => $brand->id]);
        $template = Template::first();
        $this->post('/en/create-subscription/step', ['step' => 'template', 'template_id' => $template->id]);
        $this->post('/en/create-subscription/step', ['step' => 'visibility', 'visibility' => 1]);
        $this->post('/en/create-subscription/step', ['step' => 'share', 'friends' => 2]);

        $this->post('/en/create-subscription', ['accept_tos' => 1])
            ->assertSessionHasErrors(['credentials_email', 'credentials_password']);

        $this->post('/en/create-subscription', [
            'credentials_email' => 'owner@example.com',
            'credentials_password' => 'secret-password',
            'accept_tos' => 1,
        ])->assertRedirect('/en/my-shared-subscriptions');

        $offer = Offer::where('user_id', $user->id)->first();
        $this->assertSame(['owner@example.com', 'secret-password'], $offer->credentials);
        $raw = \Illuminate\Support\Facades\DB::table('offers')->where('id', $offer->id)->value('credentials');
        $this->assertStringNotContainsString('secret-password', (string) $raw);
        $this->assertStringNotContainsString('owner@example.com', (string) $raw);
    }

    public function test_another_user_cannot_edit_or_delete_an_offer(): void
    {
        $owner = $this->user();
        $other = $this->user(['email' => 'other@example.com']);
        $brand = $this->makeBrand();
        $offer = $this->makeSeededOffer($brand, [
            'user_id' => $owner->id,
            'title' => 'My Video Plan',
            'name' => 'My Video Plan',
        ]);

        $this->actingAs($other);
        $this->get('/en/create-subscription/'.$offer->id.'/edit')->assertForbidden();
        $this->put('/en/create-subscription/'.$offer->id, [
            'name' => 'Taken Over',
            'description' => null,
            'share' => 1,
            'friends' => 1,
            'total_seats' => 2,
            'total_price' => 10.00,
            'frequency' => 2,
            'visibility' => 1,
            'currency' => 'GBP',
        ])->assertForbidden();
        $this->delete('/en/create-subscription/'.$offer->id)->assertForbidden();
        $this->assertDatabaseHas('offers', ['id' => $offer->id, 'name' => 'My Video Plan']);
    }

    public function test_owner_can_edit_and_delete_their_offer(): void
    {
        $owner = $this->user();
        $brand = $this->makeBrand();
        $offer = $this->makeSeededOffer($brand, [
            'user_id' => $owner->id,
            'title' => 'My Video Plan',
            'name' => 'My Video Plan',
        ]);

        $this->actingAs($owner);
        $this->get('/en/create-subscription/'.$offer->id.'/edit')->assertOk()->assertSee('My Video Plan');

        $this->put('/en/create-subscription/'.$offer->id, [
            'name' => 'My Video Plan Renamed',
            'description' => 'Updated.',
            'share' => 1,
            'friends' => 2,
            'total_seats' => 4,
            'total_price' => 12.00,
            'frequency' => 2,
            'visibility' => 1,
            'currency' => 'GBP',
        ])->assertRedirect('/en/my-shared-subscriptions');

        $this->assertSame('3.00', (string) $offer->fresh()->price);
        $this->assertSame('My Video Plan Renamed', $offer->fresh()->name);

        $this->delete('/en/create-subscription/'.$offer->id)->assertRedirect('/en/my-shared-subscriptions');
        $this->assertDatabaseMissing('offers', ['id' => $offer->id]);
    }

    public function test_published_offer_renders_on_the_brand_page_and_sets_the_join_price(): void
    {
        $user = $this->user();
        $brand = $this->makeBrand();
        $this->makeSeededOffer($brand);
        $this->makeSeededOffer($brand, [
            'user_id' => $user->id,
            'status' => 'active',
            'title' => 'Amazon Prime Video - with ads',
            'name' => 'Amazon Prime Video - with ads',
            'price' => 1.99,
            'seller_name' => $user->name,
            'seats_available' => 2,
            'sort_order' => 5,
        ]);

        $response = $this->actingAs($user)->get('/en/brand/amazon');

        $response->assertOk();
        $response->assertSee('Amazon Prime Video - with ads');
        $response->assertSee($user->name);
        $response->assertSee('£1.99');
        $response->assertSee('2 seats remaining');
        $content = $response->getContent();
        $this->assertNotFalse($content);
        $this->assertMatchesRegularExpression('/model-header-offer-card--join.*?<strong>£1\.99/s', $content);
    }

    public function test_draft_offer_does_not_render_on_the_brand_page(): void
    {
        $user = $this->user();
        $brand = $this->makeBrand();
        $this->makeSeededOffer($brand, [
            'user_id' => $user->id,
            'status' => 'draft',
            'title' => 'Draft Plan',
            'name' => 'Draft Plan',
            'price' => 0.50,
            'is_active' => false,
        ]);

        $response = $this->get('/en/brand/amazon');

        $response->assertOk();
        $response->assertDontSee('Draft Plan');
    }

    public function test_published_offer_renders_in_my_shared_subscriptions(): void
    {
        $user = $this->user();
        $brand = $this->makeBrand();
        $this->makeSeededOffer($brand, [
            'user_id' => $user->id,
            'status' => 'active',
            'title' => 'My Video Plan',
            'name' => 'My Video Plan',
            'price' => 1.99,
        ]);

        $response = $this->actingAs($user)->get('/en/my-shared-subscriptions');

        $response->assertOk();
        $response->assertSee('My Video Plan');
        $response->assertSee('/en/create-subscription" class="btn btn-sharesub green mb-2 mb-sm-0', false);
        $response->assertSee('£1.99 / month');
    }

    public function test_propose_links_and_calculator_create_url_point_at_the_wizard(): void
    {
        $brand = $this->makeBrand();
        $this->makeTemplate($brand, ['slug' => 'audible', 'name' => 'Audible']);

        $response = $this->get('/en/subscriptions');

        $response->assertOk();
        $response->assertSee('href="/en/create-subscription" class="btn btn-sharesub green', false);
        $response->assertDontSee('href="/en/register.html" class="btn btn-sharesub green', false);

        $home = $this->get('/en');
        $home->assertOk();
        $home->assertSee('create-subscription?template=', false);
    }

    public function test_deep_link_preselects_template_and_country(): void
    {
        $user = $this->user();
        $country = $this->makeCountry();
        $brand = $this->makeBrand();
        $template = $this->makeTemplate($brand, ['slug' => 'audible']);

        $response = $this->actingAs($user)
            ->get('/en/create-subscription?template=audible&country=united-kingdom');

        $response->assertOk();
        $response->assertSee('Step 1 of 6: Country');
        $response->assertDontSee('Step 1 of 8');

        $offer = Offer::where('user_id', $user->id)->where('status', 'draft')->first();
        $this->assertNotNull($offer);
        $this->assertSame($template->id, (int) $offer->template_id);
        $this->assertSame($country->id, (int) $offer->country_id);
    }
}
