<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The shared list rows use the real .subscriptions-item markup (Raw
 * Pages 2, spec S3-S8): name, Public and Created on pills, seat icons
 * with the remaining count, total price big with the per-subscriber
 * price in the fee line, and View details plus Chat actions. There is
 * no Edit or Delete button on a row: those live on the detail page
 * (Edit) and the Management tab (end subscription).
 */
class UserSharedSubscriptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function derivedHash(Offer $offer): string
    {
        return substr(md5('offer:'.$offer->id), 0, 7);
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

    protected function makeCategory(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'name' => 'Video',
            'slug' => 'tv',
            'sort_order' => 0,
            'is_active' => true,
        ], $overrides));
    }

    /** A fresh wizard draft, exactly as newDraft stores it. */
    protected function makeFreshDraft(User $user, array $overrides = []): Offer
    {
        return Offer::create(array_merge([
            'user_id' => $user->id,
            'status' => 'draft',
            'title' => '',
            'price' => 0,
            'currency' => 'GBP',
            'share' => 1,
            'friends' => 3,
            'total_seats' => 3,
            'frequency' => 2,
            'is_active' => false,
        ], $overrides));
    }

    public function test_fresh_draft_renders_a_name_instead_of_a_blank(): void
    {
        $user = $this->user();
        $this->makeFreshDraft($user);

        $content = $this->actingAs($user)->get('/en/my-shared-subscriptions')
            ->assertOk()->getContent();

        $this->assertStringContainsString('<div class="subscriptions-item-name">Draft subscription</div>', $content);
        $this->assertStringContainsString('3 seats remaining', $content);
        $this->assertStringNotContainsString('<div class="subscriptions-item-name"></div>', $content);
    }

    public function test_draft_with_a_brand_but_no_name_renders_the_brand_name(): void
    {
        $user = $this->user();
        $brand = $this->makeBrand(['name' => 'Nord VPN', 'slug' => 'nord-vpn']);
        $this->makeFreshDraft($user, ['brand_id' => $brand->id]);

        $content = $this->actingAs($user)->get('/en/my-shared-subscriptions')
            ->assertOk()->getContent();

        $this->assertStringContainsString('<div class="subscriptions-item-name">Nord VPN</div>', $content);
    }

    public function test_draft_with_a_template_but_no_name_renders_the_template_name(): void
    {
        $user = $this->user();
        $brand = $this->makeBrand(['name' => 'Nord VPN', 'slug' => 'nord-vpn']);
        $template = $this->makeTemplate($brand, ['name' => 'Nordpass', 'slug' => 'nordpass']);
        $this->makeFreshDraft($user, [
            'brand_id' => $brand->id,
            'template_id' => $template->id,
        ]);

        $content = $this->actingAs($user)->get('/en/my-shared-subscriptions')
            ->assertOk()->getContent();

        $this->assertStringContainsString('<div class="subscriptions-item-name">Nordpass</div>', $content);
    }

    public function test_draft_with_a_category_but_no_name_renders_the_category_name(): void
    {
        $user = $this->user();
        $category = $this->makeCategory(['name' => 'Music', 'slug' => 'music']);
        $this->makeFreshDraft($user, ['category_id' => $category->id]);

        $content = $this->actingAs($user)->get('/en/my-shared-subscriptions')
            ->assertOk()->getContent();

        $this->assertStringContainsString('<div class="subscriptions-item-name">Music</div>', $content);
    }

    public function test_offer_name_wins_over_brand_and_template_names(): void
    {
        $user = $this->user();
        $brand = $this->makeBrand();
        $template = $this->makeTemplate($brand);
        $offer = $this->makeFreshDraft($user, [
            'status' => 'active',
            'name' => 'My Video Plan',
            'title' => 'My Video Plan',
            'brand_id' => $brand->id,
            'template_id' => $template->id,
            'price' => 1.99,
        ]);

        $content = $this->actingAs($user)->get('/en/my-shared-subscriptions')
            ->assertOk()->getContent();

        $this->assertStringContainsString('<div class="subscriptions-item-name">My Video Plan</div>', $content);
        $this->assertStringContainsString('href="/en/my-shared-subscriptions/'.$this->derivedHash($offer).'"', $content);
        // This draft has no members, so it renders no Chat button: a
        // thread URL would have nothing to open.
        $this->assertStringNotContainsString('href="/en/my-chat/subscription/'.$this->derivedHash($offer).'"', $content);
    }

    public function test_row_renders_pills_prices_and_view_details_and_chat(): void
    {
        $user = $this->user();
        $offer = $this->makeFreshDraft($user, [
            'status' => 'active',
            'name' => 'My Video Plan',
            'title' => 'My Video Plan',
            'price' => 1.99,
        ]);

        $content = $this->actingAs($user)->get('/en/my-shared-subscriptions')
            ->assertOk()->getContent();

        $this->assertStringContainsString('<span class="pill pill-navy">Public</span>', $content);
        $this->assertStringContainsString('Created on '.now()->format('Y/m/d'), $content);
        $this->assertStringContainsString('3 seats remaining', $content);
        // No stored total price: the big price is the per-seat price
        // times the seat count (1.99 x 3 = 5.97).
        $this->assertStringContainsString('<span class="t3-bold">£5</span>.97', $content);
        $this->assertStringContainsString('Each subscriber pays you <strong>£1.99 / month</strong>', $content);
        $this->assertStringContainsString('View details', $content);
        $this->assertStringContainsString('Chat', $content);
        // The real row has no Edit or Delete control.
        $this->assertStringNotContainsString('href="/en/create-subscription/'.$offer->id.'/edit"', $content);
        $this->assertStringNotContainsString('Delete this subscription?', $content);
    }
}
