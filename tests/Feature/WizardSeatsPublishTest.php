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

/**
 * Wizard seats bug: a template whose total seats exceed its shareable
 * seats (Spotify Premium Family: 6 seats, 5 shareable) could never
 * publish. The Price step carries no total_seats field on the template
 * path, by design the draft keeps the template's own seat count, but
 * publish validation capped total_seats at the shareable cap, so it
 * always failed with "Choose the total number of seats." no matter
 * what the user picked at the Price step.
 */
class WizardSeatsPublishTest extends TestCase
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
            'name' => 'Spotify',
            'slug' => 'spotify',
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
            'name' => 'Spotify Premium Family',
            'slug' => 'spotify-family',
            'seats' => 6,
            'shareable_seats' => 5,
            'available_seats' => 5,
            'cost_sharing' => 1,
            'credentials_type' => 0,
            'frequency' => 2,
            'price_with_fees' => 3.67,
            'retail_price' => 21.99,
            'currency' => 'GBP',
            'description' => 'Spotify Premium Family.',
            'sort_order' => 0,
            'is_active' => true,
        ], $overrides));
    }

    protected function walkToShare(User $user, Country $country, Brand $brand, Template $template): void
    {
        $this->actingAs($user);
        $this->get('/en/create-subscription')->assertOk();
        $this->post('/en/create-subscription/step', ['step' => 'country', 'country_id' => $country->id])
            ->assertRedirect('/en/create-subscription?step=brand');
        $this->post('/en/create-subscription/step', ['step' => 'brand', 'brand_id' => $brand->id])
            ->assertRedirect('/en/create-subscription?step=template');
        $this->post('/en/create-subscription/step', ['step' => 'template', 'template_id' => $template->id])
            ->assertRedirect('/en/create-subscription?step=visibility');
        $this->post('/en/create-subscription/step', ['step' => 'visibility', 'visibility' => 1])
            ->assertRedirect('/en/create-subscription?step=share');
    }

    protected function draftFor(User $user): Offer
    {
        $offer = Offer::where('user_id', $user->id)->where('status', 'draft')->first();
        $this->assertNotNull($offer);

        return $offer;
    }

    public function test_template_path_publishes_when_total_seats_exceed_shareable_seats(): void
    {
        $user = $this->user();
        $country = $this->makeCountry();
        $brand = $this->makeBrand();
        $template = $this->makeTemplate($brand);

        $this->walkToShare($user, $country, $brand, $template);

        // The Price step on the template path posts only the offered
        // seats (friends); there is no total_seats field in its form.
        $this->post('/en/create-subscription/step', ['step' => 'share', 'friends' => 3])
            ->assertRedirect('/en/create-subscription?step=additional');

        $this->post('/en/create-subscription', [
            'secured_mode' => 'shared',
            'secured_description_shared' => 'I share the login with all members by email.',
            'accept_tos' => 1,
        ])->assertRedirect('/en/my-shared-subscriptions');

        $offer = Offer::where('user_id', $user->id)->first();
        $this->assertNotNull($offer);
        $this->assertNotSame('draft', $offer->status);
        $this->assertSame(6, (int) $offer->total_seats);
        $this->assertSame(6, (int) $offer->seats_total);
        $this->assertSame(3, (int) $offer->friends);
        $this->assertSame(3, (int) $offer->seats_available);
        $this->assertSame('3.67', (string) $offer->price);
    }

    public function test_share_step_keeps_template_total_seats_on_the_draft(): void
    {
        $user = $this->user();
        $country = $this->makeCountry();
        $brand = $this->makeBrand();
        $template = $this->makeTemplate($brand);

        $this->walkToShare($user, $country, $brand, $template);

        $draft = $this->draftFor($user);
        $this->assertSame(6, (int) $draft->total_seats);

        $this->post('/en/create-subscription/step', ['step' => 'share', 'friends' => 3])
            ->assertRedirect('/en/create-subscription?step=additional');

        $draft = $this->draftFor($user);
        $this->assertSame(6, (int) $draft->total_seats);
        $this->assertSame(6, (int) $draft->seats_total);
        $this->assertSame(3, (int) $draft->friends);
        $this->assertSame(3, (int) $draft->seats_available);
    }

    public function test_template_with_three_seats_and_two_shareable_publishes(): void
    {
        $user = $this->user();
        $country = $this->makeCountry();
        $brand = $this->makeBrand(['name' => 'Amazon', 'slug' => 'amazon']);
        $template = $this->makeTemplate($brand, [
            'name' => 'Amazon Prime Video - with ads',
            'slug' => 'amazon-prime-video',
            'seats' => 3,
            'shareable_seats' => 2,
            'available_seats' => 2,
            'retail_price' => 8.99,
            'price_with_fees' => 3.00,
        ]);

        $this->walkToShare($user, $country, $brand, $template);

        $this->post('/en/create-subscription/step', ['step' => 'share', 'friends' => 2])
            ->assertRedirect('/en/create-subscription?step=additional');

        $this->post('/en/create-subscription', [
            'secured_mode' => 'shared',
            'secured_description_shared' => 'I share the login with all members by email.',
            'accept_tos' => 1,
        ])->assertRedirect('/en/my-shared-subscriptions');

        $offer = Offer::where('user_id', $user->id)->first();
        $this->assertNotNull($offer);
        $this->assertNotSame('draft', $offer->status);
        $this->assertSame(3, (int) $offer->total_seats);
        $this->assertSame(2, (int) $offer->friends);
    }

    public function test_custom_path_share_step_persists_total_seats_to_the_draft(): void
    {
        $user = $this->user();
        $country = $this->makeCountry();
        $category = $this->makeCategory();

        $this->actingAs($user);
        $this->get('/en/create-subscription')->assertOk();
        $this->post('/en/create-subscription/step', ['step' => 'country', 'country_id' => $country->id]);
        $this->post('/en/create-subscription/step', ['step' => 'brand', 'brand_id' => '']);
        $this->post('/en/create-subscription/step', [
            'step' => 'template',
            'name' => 'My Chess Club',
            'description' => 'A shared chess subscription.',
        ]);
        $this->post('/en/create-subscription/step', ['step' => 'category', 'category_id' => $category->id]);
        $this->post('/en/create-subscription/step', ['step' => 'visibility', 'visibility' => 1]);
        $this->post('/en/create-subscription/step', ['step' => 'currency', 'currency' => 'EUR']);

        $this->post('/en/create-subscription/step', [
            'step' => 'share',
            'total_price' => 15.99,
            'friends' => 2,
            'total_seats' => 4,
        ])->assertRedirect('/en/create-subscription?step=additional');

        $draft = $this->draftFor($user);
        $this->assertSame(4, (int) $draft->total_seats);
        $this->assertSame(4, (int) $draft->seats_total);
        $this->assertSame(2, (int) $draft->friends);

        $this->post('/en/create-subscription', [
            'secured_mode' => 'shared',
            'secured_description_shared' => 'I send the invite link to each member.',
            'accept_tos' => 1,
        ])->assertRedirect('/en/my-shared-subscriptions');

        $offer = Offer::where('user_id', $user->id)->first();
        $this->assertNotNull($offer);
        $this->assertNotSame('draft', $offer->status);
        $this->assertSame(4, (int) $offer->total_seats);
    }
}
