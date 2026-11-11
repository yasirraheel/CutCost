<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Offer;
use App\Models\OfferMember;
use App\Models\User;
use Database\Seeders\RawPages2DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatLinkReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private User $demo;

    private Offer $offer;

    private OfferMember $thread;

    protected function setUp(): void
    {
        parent::setUp();

        $this->demo = User::factory()->create([
            'email' => 'demo@example.com',
            'first_name' => 'Demo',
            'last_name' => 'User',
            'username' => 'demo-user',
            'role' => 'user',
        ]);
        User::factory()->create([
            'email' => 'admin@example.com',
            'first_name' => 'Demo',
            'last_name' => 'Admin',
            'username' => 'demo-admin',
            'role' => 'admin',
        ]);
        $brand = Brand::create([
            'name' => 'Nordpass',
            'slug' => 'nordpass',
            'price' => 0.96,
            'full_price' => 9.99,
            'currency' => 'GBP',
            'currency_symbol' => '£',
            'is_featured' => true,
            'sort_order' => 0,
            'is_active' => true,
        ]);
        $this->offer = Offer::create([
            'brand_id' => $brand->id,
            'user_id' => $this->demo->id,
            'title' => 'Nordpass',
            'price' => 0.96,
            'currency' => 'GBP',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->seed(RawPages2DemoSeeder::class);

        $this->thread = OfferMember::where('offer_id', $this->offer->id)
            ->where('username', 'demo-member-a')
            ->firstOrFail();
    }

    private function derivedHash(Offer $offer): string
    {
        return substr(md5('offer:'.$offer->id), 0, 7);
    }

    public function test_shared_list_chat_link_by_offer_hash_opens_first_thread(): void
    {
        $response = $this->actingAs($this->demo)->get('/en/my-chat/subscription/'.$this->derivedHash($this->offer));

        $response->assertOk();
        $response->assertSee('demo-member-a', false);
    }

    public function test_chat_detail_by_stored_offer_hash_opens_thread(): void
    {
        $this->offer->update(['hash' => 'd07294a']);

        $response = $this->actingAs($this->demo)->get('/en/my-chat/subscription/d07294a');

        $response->assertOk();
        $response->assertSee('demo-member-a', false);
    }

    public function test_contact_link_shape_opens_messages_view(): void
    {
        $response = $this->actingAs($this->demo)
            ->get('/en/my-chat/subscription/'.$this->offer->id.'/'.$this->thread->id.'/messages');

        $response->assertOk();
        $response->assertSee('demo-member-a', false);
    }

    public function test_unknown_chat_identifier_404s(): void
    {
        $this->actingAs($this->demo)->get('/en/my-chat/subscription/nope123')->assertNotFound();
        $this->actingAs($this->demo)->get('/en/my-chat/subscription/99999')->assertNotFound();
    }

    public function test_other_users_offer_hash_404s(): void
    {
        $other = User::factory()->create(['role' => 'user']);
        $otherOffer = Offer::create([
            'brand_id' => $this->offer->brand_id,
            'user_id' => $other->id,
            'title' => 'Nordpass',
            'price' => 0.96,
            'currency' => 'GBP',
            'status' => 'active',
            'is_active' => true,
            'hash' => 'zzother1',
        ]);
        OfferMember::create([
            'offer_id' => $otherOffer->id,
            'user_id' => $other->id,
            'username' => 'other-member',
            'initials' => 'OM',
            'status' => OfferMember::STATUS_CURRENT,
            'joined_at' => now(),
        ]);

        $this->actingAs($this->demo)->get('/en/my-chat/subscription/zzother1')->assertNotFound();
        $this->actingAs($this->demo)->get('/en/my-chat/subscription/'.$this->derivedHash($otherOffer))->assertNotFound();
    }

    public function test_shared_list_hides_chat_button_for_offer_without_members(): void
    {
        $draft = Offer::create([
            'brand_id' => $this->offer->brand_id,
            'user_id' => $this->demo->id,
            'title' => 'Nordpass Draft',
            'price' => 0.00,
            'currency' => 'GBP',
            'status' => 'draft',
            'is_active' => true,
        ]);

        $content = $this->actingAs($this->demo)->get('/en/my-shared-subscriptions')->assertOk()->getContent();

        $this->assertStringNotContainsString('/en/my-chat/subscription/'.$this->derivedHash($draft), $content);
        $this->assertStringContainsString('/en/my-chat/subscription/'.$this->derivedHash($this->offer), $content);
    }

    public function test_detail_page_hides_chat_button_for_offer_without_members(): void
    {
        $draft = Offer::create([
            'brand_id' => $this->offer->brand_id,
            'user_id' => $this->demo->id,
            'title' => 'Nordpass Draft',
            'price' => 0.00,
            'currency' => 'GBP',
            'status' => 'draft',
            'is_active' => true,
        ]);

        $content = $this->actingAs($this->demo)
            ->get('/en/my-shared-subscriptions/'.$this->derivedHash($draft))
            ->assertOk()->getContent();

        $this->assertStringNotContainsString('stats-chat', $content);
    }

    public function test_view_subscription_link_from_chat_detail_resolves(): void
    {
        $response = $this->actingAs($this->demo)->get('/en/my-chat/subscription/'.$this->thread->id);

        $response->assertOk();
        $response->assertSee('/en/my-shared-subscriptions/'.$this->derivedHash($this->offer), false);

        $this->actingAs($this->demo)->get('/en/my-shared-subscriptions/'.$this->derivedHash($this->offer))->assertOk();
    }
}
