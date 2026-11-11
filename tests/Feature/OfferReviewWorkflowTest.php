<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Country;
use App\Models\Offer;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => 'user'], $overrides));
    }

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
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

    protected function makePendingOffer(User $owner, ?Brand $brand = null, array $overrides = []): Offer
    {
        $brand ??= $this->makeBrand();

        return Offer::create(array_merge([
            'brand_id' => $brand->id,
            'user_id' => $owner->id,
            'status' => Offer::STATUS_PENDING_REVIEW,
            'title' => 'My Video Plan',
            'name' => 'My Video Plan',
            'price' => 1.99,
            'currency' => 'GBP',
            'seats_total' => 6,
            'seats_available' => 2,
            'seller_name' => $owner->name,
            'sort_order' => 0,
            'is_active' => true,
        ], $overrides));
    }

    public function test_published_offer_starts_in_pending_review(): void
    {
        $user = $this->user();
        $country = $this->makeCountry();
        $brand = $this->makeBrand();
        $template = $this->makeTemplate($brand);

        $this->actingAs($user);

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

        $this->post('/en/create-subscription', [
            'secured_mode' => 'shared',
            'secured_description_shared' => 'I share the login with all members by email.',
            'accept_tos' => 1,
        ])->assertRedirect('/en/my-shared-subscriptions');

        $offer = Offer::where('user_id', $user->id)->first();
        $this->assertNotNull($offer);
        $this->assertSame(Offer::STATUS_PENDING_REVIEW, $offer->status);
        $this->assertTrue($offer->isPendingReview());
        $this->assertFalse($offer->isPublished());
    }

    public function test_admin_approve_activates_a_pending_offer(): void
    {
        $owner = $this->user();
        $offer = $this->makePendingOffer($owner);

        $response = $this->actingAs($this->admin())
            ->post(route('admin.offers.approve', $offer));

        $response->assertRedirect(route('admin.offers.index'));
        $this->assertSame(Offer::STATUS_ACTIVE, $offer->fresh()->status);
        $this->assertTrue($offer->fresh()->isPublished());
    }

    public function test_admin_reject_marks_offer_rejected_and_it_still_shows(): void
    {
        $owner = $this->user();
        $offer = $this->makePendingOffer($owner);

        $response = $this->actingAs($this->admin())
            ->post(route('admin.offers.reject', $offer));

        $response->assertRedirect(route('admin.offers.index'));
        $this->assertSame(Offer::STATUS_REJECTED, $offer->fresh()->status);
        $this->assertTrue($offer->fresh()->isRejected());

        $this->actingAs($this->admin())->get(route('admin.offers.index'))
            ->assertOk()
            ->assertSee('My Video Plan')
            ->assertSee('Rejected');

        $this->actingAs($owner)->get('/en/my-shared-subscriptions')
            ->assertOk()
            ->assertSee('My Video Plan')
            ->assertSee('Rejected');
    }

    public function test_user_list_shows_being_reviewed_while_pending(): void
    {
        $owner = $this->user();
        $this->makePendingOffer($owner);

        $response = $this->actingAs($owner)->get('/en/my-shared-subscriptions');

        $response->assertOk();
        $response->assertSee('My Video Plan');
        $response->assertSee('Being reviewed');
    }

    public function test_user_list_shows_no_review_pill_once_approved(): void
    {
        $owner = $this->user();
        $offer = $this->makePendingOffer($owner);

        $this->actingAs($this->admin())->post(route('admin.offers.approve', $offer));

        $response = $this->actingAs($owner)->get('/en/my-shared-subscriptions');

        $response->assertOk();
        $response->assertSee('My Video Plan');
        $response->assertDontSee('Being reviewed');
    }

    public function test_admin_list_shows_approve_and_reject_only_for_pending_offers(): void
    {
        $owner = $this->user();
        $brand = $this->makeBrand();
        $pending = $this->makePendingOffer($owner, $brand);
        $active = $this->makePendingOffer($owner, $brand, [
            'status' => Offer::STATUS_ACTIVE,
            'title' => 'Already Live Plan',
            'name' => 'Already Live Plan',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.offers.index'));

        $response->assertOk();
        $response->assertSee('My Video Plan');
        $response->assertSee('Pending review');
        $response->assertSee(route('admin.offers.approve', $pending), false);
        $response->assertSee(route('admin.offers.reject', $pending), false);
        $response->assertDontSee(route('admin.offers.approve', $active), false);
        $response->assertDontSee(route('admin.offers.reject', $active), false);
    }

    public function test_non_admin_cannot_approve_or_reject(): void
    {
        $owner = $this->user();
        $offer = $this->makePendingOffer($owner);

        $this->actingAs($owner)->post(route('admin.offers.approve', $offer))
            ->assertForbidden();

        $this->assertSame(Offer::STATUS_PENDING_REVIEW, $offer->fresh()->status);
    }

    public function test_pending_offer_does_not_hide_dashboard_onboarding(): void
    {
        $owner = $this->user();
        $this->makePendingOffer($owner);

        $response = $this->actingAs($owner)->get('/en/dashboard');

        $response->assertOk();
        $response->assertSee('share-section dashboard', false);
    }
}
