<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferApprovalBothTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    protected function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => 'user'], $overrides));
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

    protected function makePendingOffer(User $owner, Brand $brand, array $overrides = []): Offer
    {
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

    protected function createOfferAsAdmin(Brand $brand, string $title = 'Admin Added Plan'): Offer
    {
        // The GBP currency row is already seeded in the test database.
        $this->actingAs($this->admin());

        $response = $this->post(route('admin.offers.store'), [
            'brand_id' => $brand->id,
            'title' => $title,
            'price' => '3.50',
            'currency' => 'GBP',
            'seats_total' => 5,
            'seats_available' => 2,
            'seller_name' => 'Admin Seller',
            'sort_order' => 1,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.offers.index'));

        return Offer::where('title', $title)->firstOrFail();
    }

    public function test_user_created_pending_offer_is_approvable_from_admin_list(): void
    {
        $owner = $this->user();
        $brand = $this->makeBrand();
        $offer = $this->makePendingOffer($owner, $brand);

        $this->actingAs($this->admin())->get(route('admin.offers.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('My Video Plan')
            ->assertSee(route('admin.offers.approve', $offer), false);

        $this->actingAs($this->admin())->post(route('admin.offers.approve', $offer))
            ->assertRedirect(route('admin.offers.index'));

        $this->assertSame(Offer::STATUS_ACTIVE, $offer->fresh()->status);
    }

    public function test_admin_created_offer_lands_in_pending_review(): void
    {
        $brand = $this->makeBrand();
        $offer = $this->createOfferAsAdmin($brand);

        $this->assertSame(Offer::STATUS_PENDING_REVIEW, $offer->status);
        $this->assertTrue($offer->isPendingReview());
        $this->assertFalse($offer->isPublished());
    }

    public function test_admin_created_pending_offer_is_approvable_like_a_user_offer(): void
    {
        $brand = $this->makeBrand();
        $offer = $this->createOfferAsAdmin($brand);

        $this->actingAs($this->admin())->get(route('admin.offers.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('Admin Added Plan')
            ->assertSee(route('admin.offers.approve', $offer), false);

        $this->actingAs($this->admin())->post(route('admin.offers.approve', $offer))
            ->assertRedirect(route('admin.offers.index'));

        $this->assertSame(Offer::STATUS_ACTIVE, $offer->fresh()->status);
        $this->assertTrue($offer->fresh()->isPublished());
    }

    public function test_approve_clears_being_reviewed_pill_in_user_list(): void
    {
        $owner = $this->user();
        $brand = $this->makeBrand();
        $offer = $this->makePendingOffer($owner, $brand);

        $this->actingAs($owner)->get('/en/my-shared-subscriptions')
            ->assertOk()
            ->assertSee('Being reviewed');

        $this->actingAs($this->admin())->post(route('admin.offers.approve', $offer));

        $this->actingAs($owner)->get('/en/my-shared-subscriptions')
            ->assertOk()
            ->assertSee('My Video Plan')
            ->assertDontSee('Being reviewed');
    }

    public function test_reject_marks_offer_rejected_and_it_still_shows(): void
    {
        $owner = $this->user();
        $brand = $this->makeBrand();
        $offer = $this->makePendingOffer($owner, $brand);

        $this->actingAs($this->admin())->post(route('admin.offers.reject', $offer))
            ->assertRedirect(route('admin.offers.index'));

        $this->assertSame(Offer::STATUS_REJECTED, $offer->fresh()->status);

        $this->actingAs($this->admin())->get(route('admin.offers.index', ['status' => 'rejected']))
            ->assertOk()
            ->assertSee('My Video Plan')
            ->assertSee('Rejected');

        $this->actingAs($owner)->get('/en/my-shared-subscriptions')
            ->assertOk()
            ->assertSee('My Video Plan')
            ->assertSee('Rejected');
    }

    public function test_pending_review_filter_shows_only_pending_offers(): void
    {
        $owner = $this->user();
        $brand = $this->makeBrand();
        $this->makePendingOffer($owner, $brand);
        $this->makePendingOffer($owner, $brand, [
            'status' => Offer::STATUS_ACTIVE,
            'title' => 'Already Live Plan',
            'name' => 'Already Live Plan',
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.offers.index', ['status' => 'pending']));

        $response->assertOk();
        $response->assertSee('My Video Plan');
        $response->assertSee('Pending review (1)', false);
        $response->assertDontSee('Already Live Plan');
    }
}
