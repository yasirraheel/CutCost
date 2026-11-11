<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Offer;
use App\Models\User;
use Database\Seeders\OfferSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OffersTest extends TestCase
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

    public function test_brand_detail_page_shows_a_seeded_offer_price(): void
    {
        $brand = $this->makeBrand();
        $this->makeOffer($brand);

        $response = $this->get('/en/brand/amazon');

        $response->assertStatus(200);
        $response->assertSee('£4.23');
        $response->assertSee('socheatalim');
        $response->assertSee('Amazon Prime Video - with ads');
        $response->assertSee('2 seats remaining');
    }

    public function test_cheapest_offer_drives_the_header_price(): void
    {
        $brand = $this->makeBrand();
        $this->makeOffer($brand, ['price' => 4.94, 'sort_order' => 1, 'seller_name' => 'peteconstantinides']);
        $this->makeOffer($brand, ['price' => 4.23, 'sort_order' => 0, 'seller_name' => 'nithint']);

        $response = $this->get('/en/brand/amazon');

        $response->assertStatus(200);
        $response->assertSee('£4.23');
        $response->assertSee('peteconstantinides');
        $response->assertDontSee('£1.85');
    }

    public function test_brand_detail_without_offers_keeps_the_captured_page(): void
    {
        $this->makeBrand();

        $response = $this->get('/en/brand/amazon');

        $response->assertStatus(200);
        $response->assertSee('£1.85');
        $response->assertSee('socheatalim');
    }

    public function test_offer_seeder_extracts_the_captured_amazon_offers(): void
    {
        $this->makeBrand();

        $this->seed(OfferSeeder::class);

        $this->assertSame(9, Offer::count());
        $this->assertSame(4.23, (float) Offer::orderBy('price')->first()->price);
        $this->assertSame(4.94, (float) Offer::orderByDesc('price')->first()->price);
        $this->assertSame('peteconstantinides', Offer::orderByDesc('price')->first()->seller_name);
        $this->assertSame(1, Offer::where('seller_name', 'socheatalim')->first()->seats_available);
        $this->assertNull(Offer::where('seller_name', 'socheatalim')->first()->seats_total);
        $this->assertSame(
            'Discover the Amazon subscription sharing on Sharesub, and join a group for £1.85/month. The best way to get Amazon at a low price.',
            Brand::where('slug', 'amazon')->first()->tagline,
        );
    }

    public function test_unknown_brand_still_404s(): void
    {
        $this->get('/en/brand/no-such-brand')->assertStatus(404);
    }

    public function test_guest_cannot_reach_admin_offers_routes(): void
    {
        $this->get(route('admin.offers.index'))->assertRedirect('/en/userlogin');
        $this->get(route('admin.offers.create'))->assertRedirect('/en/userlogin');
        $this->post(route('admin.offers.store'), [])->assertRedirect('/en/userlogin');
    }

    public function test_admin_offer_crud_happy_path(): void
    {
        $brand = $this->makeBrand();
        $this->actingAs($this->admin());

        $create = $this->post(route('admin.offers.store'), [
            'brand_id' => $brand->id,
            'title' => 'Test Offer Plan',
            'price' => '3.50',
            'currency' => 'GBP',
            'seats_total' => 5,
            'seats_available' => 2,
            'seller_name' => 'Test Seller',
            'sort_order' => 1,
            'is_active' => '1',
        ]);
        $create->assertRedirect(route('admin.offers.index'));
        $this->assertDatabaseHas('offers', ['title' => 'Test Offer Plan', 'price' => 3.50]);

        $this->get(route('admin.offers.index'))->assertOk()->assertSee('Test Offer Plan');

        $offer = Offer::where('title', 'Test Offer Plan')->firstOrFail();

        $update = $this->put(route('admin.offers.update', $offer), [
            'brand_id' => $brand->id,
            'title' => 'Test Offer Plan',
            'price' => '3.75',
            'currency' => 'GBP',
            'seats_total' => 5,
            'seats_available' => 1,
            'seller_name' => 'Test Seller',
            'sort_order' => 1,
            'is_active' => '1',
        ]);
        $update->assertRedirect(route('admin.offers.edit', $offer));
        $this->assertDatabaseHas('offers', ['title' => 'Test Offer Plan', 'price' => 3.75]);

        $delete = $this->delete(route('admin.offers.destroy', $offer));
        $delete->assertRedirect(route('admin.offers.index'));
        $this->assertDatabaseMissing('offers', ['title' => 'Test Offer Plan']);
    }
}
