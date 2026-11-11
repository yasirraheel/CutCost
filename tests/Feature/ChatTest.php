<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\ChatMessage;
use App\Models\Offer;
use App\Models\OfferMember;
use App\Models\User;
use Database\Seeders\RawPages2DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
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

    public function test_guest_is_redirected_to_login_from_chat_list(): void
    {
        $this->get('/en/my-chat/subscriptions')->assertRedirect('/en/userlogin');
    }

    public function test_chat_list_renders_conversations_from_the_database(): void
    {
        $response = $this->actingAs($this->demo)->get('/en/my-chat/subscriptions');

        $response->assertOk();
        $response->assertSee('Nordpass', false);
        $response->assertSee('3 subscribers', false);
        $response->assertSee('Open conversation', false);
        $response->assertSee('/en/my-chat/subscription/'.$this->thread->id, false);
        $response->assertSee('/en/my-chat/subscription/'.$this->thread->id.'/messages', false);
        $response->assertSee('window.check_chat_url = "/en/my-chat/unread"', false);
        $response->assertSee('https://help.sharesub.com/', false);
        $response->assertSee('/en/my-bank-account', false);
        $response->assertDontSee('No conversation', false);
        $response->assertDontSee('chat--noconv', false);
        $response->assertDontSee('href="#offline" data-open-support', false);
    }

    public function test_chat_list_html_alias_redirects_to_the_canonical_page(): void
    {
        $this->actingAs($this->demo)
            ->get('/en/my-chat/subscriptions.html')
            ->assertRedirect('/en/my-chat/subscriptions', 301);
    }

    public function test_chat_list_shows_the_empty_state_for_a_user_with_no_conversations(): void
    {
        $other = User::factory()->create([
            'email' => 'plain@example.com',
            'first_name' => 'Plain',
            'last_name' => 'User',
            'username' => 'plain-user',
            'role' => 'user',
        ]);

        $response = $this->actingAs($other)->get('/en/my-chat/subscriptions');

        $response->assertOk();
        $response->assertSee('No conversation', false);
        $response->assertSee('href="/en/create-subscription"', false);
        $response->assertDontSee('href="#offline" class="chat-noconv-link"', false);
    }

    public function test_admin_is_redirected_to_the_admin_chat_section(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get('/en/my-chat/subscriptions')
            ->assertRedirect('/en/admin/chat');
    }

    public function test_chat_detail_renders_members_and_back_link(): void
    {
        $response = $this->actingAs($this->demo)->get('/en/my-chat/subscription/'.$this->thread->id);

        $response->assertOk();
        $response->assertSee('View all conversations', false);
        $response->assertSee('href="/en/my-chat/subscriptions"', false);
        $response->assertSee('href="/en/my-shared-subscriptions/', false);
        $response->assertSee('Subscribers', false);
        // Owner member first, carrying the me class and its initials.
        $response->assertSee('avatar me', false);
        $response->assertSee('DU', false);
        $response->assertSee('DA', false);
        $response->assertSee('DB', false);
        $response->assertSee('DC', false);
        $response->assertDontSee('demo-member-old', false);
        $response->assertSee('/en/my-chat/subscription/'.$this->thread->id.'/messages', false);
        // No online presence flag is rendered: presence has no source.
        $response->assertDontSee('chat-column-item-icon avatar me online', false);
        $response->assertDontSee('chat-column-item-icon avatar online', false);
    }

    public function test_chat_detail_marks_the_current_offer_and_hides_the_others_on_mobile(): void
    {
        $second = Offer::create([
            'brand_id' => $this->offer->brand_id,
            'user_id' => $this->demo->id,
            'title' => 'Demo Second',
            'price' => 1.20,
            'currency' => 'GBP',
            'status' => 'active',
            'is_active' => true,
        ]);
        OfferMember::create([
            'offer_id' => $second->id,
            'user_id' => $this->demo->id,
            'username' => 'demo-user',
            'initials' => 'DU',
            'status' => OfferMember::STATUS_CURRENT,
        ]);
        OfferMember::create([
            'offer_id' => $second->id,
            'username' => 'demo-member-a',
            'initials' => 'DA',
            'status' => OfferMember::STATUS_CURRENT,
        ]);

        $response = $this->actingAs($this->demo)->get('/en/my-chat/subscription/'.$this->thread->id);

        $response->assertOk();
        $response->assertSee('Demo Second', false);
        $response->assertSee('chat-column-item active', false);
        $response->assertSee('chat-column-item d-none d-lg-flex', false);
    }

    public function test_chat_detail_returns_404_for_another_users_thread_and_for_a_missing_member(): void
    {
        $other = User::factory()->create([
            'email' => 'other@example.com',
            'first_name' => 'Other',
            'last_name' => 'User',
            'username' => 'other-user',
            'role' => 'user',
        ]);
        $foreignOffer = Offer::create([
            'brand_id' => $this->offer->brand_id,
            'user_id' => $other->id,
            'title' => 'Foreign',
            'price' => 1.00,
            'currency' => 'GBP',
            'status' => 'active',
            'is_active' => true,
        ]);
        $foreignMember = OfferMember::create([
            'offer_id' => $foreignOffer->id,
            'username' => 'foreign-member',
            'initials' => 'FM',
            'status' => OfferMember::STATUS_CURRENT,
        ]);

        $this->actingAs($this->demo)->get('/en/my-chat/subscription/'.$foreignMember->id)->assertNotFound();
        $this->actingAs($this->demo)->get('/en/my-chat/subscription/999999')->assertNotFound();
        $this->actingAs($this->demo)->get('/en/my-chat/subscription/'.$foreignMember->id.'/messages')->assertNotFound();
    }

    public function test_messages_view_renders_the_thread_component(): void
    {
        $response = $this->actingAs($this->demo)->get('/en/my-chat/subscription/'.$this->thread->id.'/messages');

        $response->assertOk();
        $response->assertSee('data-send-message-url="/en/my-chat/subscription/'.$this->thread->id.'/message"', false);
        $response->assertSee('data-latest-messages-url="/en/my-chat/subscription/'.$this->thread->id.'/messages/latest"', false);
        $response->assertSee('data-read-url="/en/my-chat/subscription/'.$this->thread->id.'/read"', false);
        $response->assertSee('data-moderate-url="/en/my-chat/subscription/'.$this->thread->id.'/moderate"', false);
        $response->assertSee('data-chunk-length="100"', false);
        $response->assertSee('data-dest-name="demo-member-a"', false);
        $response->assertSee('data-template-name="Nordpass"', false);
        $response->assertSee('data-chat-thread', false);
        $response->assertSee('data-chat-form', false);
        $response->assertSee('href="/en/my-chat/subscription/'.$this->thread->id.'"', false);
        // No sharesub-chat element in v1: the bundled app.js mounts
        // the real component on that tag, and without keys it renders
        // every message as "false".
        $response->assertDontSee('<sharesub-chat', false);
        $response->assertDontSee('private-key', false);
        $response->assertDontSee('public-key', false);
    }

    public function test_unread_returns_the_new_message_count_as_plain_text(): void
    {
        $response = $this->actingAs($this->demo)->get('/en/my-chat/unread');

        $response->assertOk();
        $this->assertSame('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame('1', $response->getContent());

        $this->actingAs($this->demo)->post('/en/my-chat/subscription/'.$this->thread->id.'/read');

        $response = $this->actingAs($this->demo)->get('/en/my-chat/unread');
        $this->assertSame('', $response->getContent());
    }

    public function test_latest_messages_returns_the_captured_shape_newest_first(): void
    {
        $response = $this->actingAs($this->demo)
            ->getJson('/en/my-chat/subscription/'.$this->thread->id.'/messages/latest?input_timestamp=12345');

        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['done']);
        $this->assertFalse($data['has_older_messages']);
        $this->assertFalse($data['old_messages']);
        $this->assertSame(12345, $data['input_timestamp']);
        $this->assertIsInt($data['timestamp']);
        $this->assertCount(3, $data['messages']);

        $ids = array_column($data['messages'], 'id');
        $sorted = $ids;
        rsort($sorted);
        $this->assertSame($sorted, $ids);

        $first = $data['messages'][0];
        $this->assertSame('Great, thank you. Let me know once it is sent.', $first['crypted']);
        $this->assertFalse($first['is_mine']);
        $this->assertTrue($first['new']);
        $this->assertFalse($first['read']);

        $second = $data['messages'][1];
        $this->assertTrue($second['is_mine']);
        $this->assertFalse($second['new']);

        $response = $this->actingAs($this->demo)
            ->getJson('/en/my-chat/subscription/'.$this->thread->id.'/messages/latest');
        $this->assertNull($response->json('input_timestamp'));
    }

    public function test_latest_messages_accepts_the_real_component_timestamp_parameter(): void
    {
        $response = $this->actingAs($this->demo)
            ->getJson('/en/my-chat/subscription/'.$this->thread->id.'/messages/latest?timestamp=54321');

        $response->assertOk();
        $this->assertSame(54321, $response->json('input_timestamp'));
    }

    public function test_read_with_a_message_id_marks_only_that_message(): void
    {
        $new = ChatMessage::where('member_id', $this->thread->id)->where('is_new', true)->firstOrFail();

        $response = $this->actingAs($this->demo)
            ->postJson('/en/my-chat/subscription/'.$this->thread->id.'/read', ['id' => $new->id]);

        $response->assertOk();
        $this->assertSame(1, $response->json('read'));
        $this->assertTrue($new->fresh()->is_read);
    }

    public function test_moderate_accepts_the_real_component_id_field(): void
    {
        $message = ChatMessage::where('member_id', $this->thread->id)->orderBy('id')->firstOrFail();

        $response = $this->actingAs($this->demo)
            ->postJson('/en/my-chat/subscription/'.$this->thread->id.'/moderate', ['id' => $message->id]);

        $response->assertOk();
        $this->assertTrue($response->json('done'));
        $this->assertSame(1, $message->fresh()->waiting_moderation);
    }

    public function test_send_stores_a_message_and_returns_it(): void
    {
        $response = $this->actingAs($this->demo)
            ->postJson('/en/my-chat/subscription/'.$this->thread->id.'/message', ['message' => 'Hello from the test']);

        $response->assertCreated();
        $data = $response->json();
        $this->assertSame('Hello from the test', $data['crypted']);
        $this->assertTrue($data['is_mine']);
        $this->assertTrue($data['new']);
        $this->assertFalse($data['read']);
        $this->assertDatabaseHas('chat_messages', [
            'offer_id' => $this->offer->id,
            'member_id' => $this->thread->id,
            'sender_id' => $this->demo->id,
            'body' => 'Hello from the test',
        ]);
    }

    public function test_send_requires_a_message(): void
    {
        $this->actingAs($this->demo)
            ->postJson('/en/my-chat/subscription/'.$this->thread->id.'/message', ['message' => ''])
            ->assertStatus(422);
    }

    public function test_read_marks_the_thread_read(): void
    {
        $response = $this->actingAs($this->demo)
            ->postJson('/en/my-chat/subscription/'.$this->thread->id.'/read');

        $response->assertOk();
        $this->assertTrue($response->json('done'));
        $this->assertSame(1, $response->json('read'));
        $this->assertSame(0, ChatMessage::where('member_id', $this->thread->id)->where('is_new', true)->count());
        $this->assertSame(0, ChatMessage::where('member_id', $this->thread->id)->where('is_read', false)->count());
    }

    public function test_moderate_flags_a_message_for_review(): void
    {
        $message = ChatMessage::where('member_id', $this->thread->id)->orderBy('id')->firstOrFail();

        $response = $this->actingAs($this->demo)
            ->postJson('/en/my-chat/subscription/'.$this->thread->id.'/moderate', ['message_id' => $message->id]);

        $response->assertOk();
        $this->assertTrue($response->json('done'));
        $this->assertSame(1, $response->json('message.waiting_moderation'));
        $this->assertSame(1, $message->fresh()->waiting_moderation);
    }

    public function test_endpoints_return_404_for_another_users_thread(): void
    {
        $other = User::factory()->create([
            'email' => 'other2@example.com',
            'first_name' => 'Other',
            'last_name' => 'User',
            'username' => 'other2-user',
            'role' => 'user',
        ]);
        $foreignOffer = Offer::create([
            'brand_id' => $this->offer->brand_id,
            'user_id' => $other->id,
            'title' => 'Foreign',
            'price' => 1.00,
            'currency' => 'GBP',
            'status' => 'active',
            'is_active' => true,
        ]);
        $foreignMember = OfferMember::create([
            'offer_id' => $foreignOffer->id,
            'username' => 'foreign-member-2',
            'initials' => 'FM',
            'status' => OfferMember::STATUS_CURRENT,
        ]);

        $this->actingAs($this->demo)->getJson('/en/my-chat/subscription/'.$foreignMember->id.'/messages/latest')->assertNotFound();
        $this->actingAs($this->demo)->postJson('/en/my-chat/subscription/'.$foreignMember->id.'/message', ['message' => 'hi'])->assertNotFound();
        $this->actingAs($this->demo)->postJson('/en/my-chat/subscription/'.$foreignMember->id.'/read')->assertNotFound();
        $this->actingAs($this->demo)->postJson('/en/my-chat/subscription/'.$foreignMember->id.'/moderate', ['message_id' => 1])->assertNotFound();
        $this->actingAs($this->demo)->get('/en/my-chat/unread')->assertOk();
    }
}
