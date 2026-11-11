<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Offer;
use App\Models\OfferMember;
use App\Models\OfferPayment;
use App\Models\Template;
use App\Models\User;
use App\Models\WalletBalance;
use Database\Seeders\RawPages2DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Raw Pages 2, group A: dashboard, shared list and shared-subscription
 * detail page (spec-dashboard-shared.md). Fixtures are the demo rows
 * RawPages2DemoSeeder builds: one active demo offer at 0.96 with 3
 * current co-subscribers (monthly income base 0.96 x 3 = 2.88), one
 * previous member, GBP wallet rows of 2.88 and three payouts.
 */
class RawPages2DashboardSharedTest extends TestCase
{
    use RefreshDatabase;

    private function derivedHash(Offer $offer): string
    {
        return substr(md5('offer:'.$offer->id), 0, 7);
    }

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
        Brand::create([
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
        Offer::create([
            'brand_id' => Brand::where('slug', 'nordpass')->first()->id,
            'user_id' => $this->demo->id,
            'title' => 'Nordpass',
            'name' => 'Nordpass',
            'price' => 0.96,
            'currency' => 'GBP',
            'status' => 'active',
            'is_active' => true,
            'friends' => 5,
        ]);

        $this->seed(RawPages2DemoSeeder::class);
        $this->offer = Offer::where('user_id', $this->demo->id)->first();
    }

    protected User $demo;

    protected Offer $offer;

    // Dashboard ----------------------------------------------------------

    public function test_dashboard_greeting_shows_the_logged_in_first_name(): void
    {
        $content = $this->actingAs($this->demo)->get('/en/dashboard')
            ->assertOk()->getContent();

        $this->assertStringContainsString('<span class="text-green">Demo</span>', $content);
        $this->assertStringNotContainsString('<span class="text-green">Bani</span>', $content);
    }

    public function test_dashboard_stats_come_from_wallet_balances(): void
    {
        $content = $this->actingAs($this->demo)->get('/en/dashboard')
            ->assertOk()->getContent();

        // Monthly Savings is the pending monthly income (2.88 GBP) and
        // the subvalue is the total earnings "from the beginning".
        $this->assertStringContainsString('<span class="stat-value">£2.88</span>', $content);
        $this->assertStringContainsString('<span class="stat-subvalue">£2.88 from the beginning</span>', $content);
        // Refund coming this month is the available balance becoming
        // available this month, NOT the pending amount: the demo
        // available balance falls due on 9 December, so in November
        // the refund is a zero GBP line while savings stay non-zero,
        // matching the real capture's pattern.
        $this->assertStringContainsString('<span class="stat-value text-green">£0</span>', $content);
        $this->assertStringNotContainsString('€2.88', $content);
    }

    public function test_dashboard_hides_cta_and_checklist_for_user_with_active_offer(): void
    {
        $content = $this->actingAs($this->demo)->get('/en/dashboard')
            ->assertOk()->getContent();

        // The real capture has no onboarding markup at all, so the
        // blocks are removed, not hidden.
        $this->assertStringNotContainsString('Where would you like', $content);
        $this->assertStringNotContainsString('Finalize your profile', $content);
        $this->assertStringNotContainsString('share-section dashboard', $content);
        $this->assertStringNotContainsString('dashboard-profile-steps', $content);
        // Income and expenses is first, Payments below it.
        $this->assertLessThan(
            strpos($content, 'Payments</h3>'),
            strpos($content, 'Income &amp; expenses</h3>') ?: strpos($content, 'Income & expenses</h3>'),
        );
    }

    public function test_dashboard_shows_cta_and_checklist_for_fresh_user_with_no_active_offer(): void
    {
        $fresh = User::factory()->create(['role' => 'user', 'first_name' => 'Fresh']);

        $content = $this->actingAs($fresh)->get('/en/dashboard')
            ->assertOk()->getContent();

        $this->assertStringContainsString('Where would you like', $content);
        $this->assertStringContainsString('Finalize your profile', $content);
        $this->assertStringContainsString('dashboard-profile-steps', $content);
        // CTA Share button points at the wizard, not #offline.
        $this->assertStringContainsString('<a href="/en/create-subscription" class="btn-sharesub dark big">Share</a>', $content);
        // Stats keep the captured zeros for a fresh user.
        $this->assertStringContainsString('<span class="stat-value">€0</span>', $content);
    }

    public function test_dashboard_refund_two_line_join_when_two_available_rows_fall_due_this_month(): void
    {
        // Move the demo GBP available balance due into this month and
        // add a EUR available balance due this month: the refund must
        // render two lines, account currency first, EUR second.
        WalletBalance::where('user_id', $this->demo->id)
            ->where('metric', WalletBalance::METRIC_AVAILABLE)
            ->update(['available_on' => now()->startOfMonth()->addDays(8)->toDateString()]);
        WalletBalance::create([
            'user_id' => $this->demo->id,
            'metric' => WalletBalance::METRIC_AVAILABLE,
            'currency' => 'EUR',
            'amount' => 1.25,
            'available_on' => now()->startOfMonth()->addDays(8)->toDateString(),
        ]);

        $content = $this->actingAs($this->demo)->get('/en/dashboard')
            ->assertOk()->getContent();

        $this->assertStringContainsString('<span class="stat-value text-green">£2.88</span>', $content);
        $this->assertStringContainsString('<span class="stat-value text-green">€1.25</span>', $content);
        $this->assertMatchesRegularExpression(
            '/<span class="stat-value text-green">£2\.88<\/span>\s*<br>\s*<span class="stat-value text-green">€1\.25<\/span>/',
            $content,
        );
    }

    public function test_dashboard_coming_soon_lists_pending_next_month_offer_payments(): void
    {
        $content = $this->actingAs($this->demo)->get('/en/dashboard')
            ->assertOk()->getContent();

        $nextMonth = now()->startOfMonth()->addMonth()->format('Y/m/d');
        $this->assertStringContainsString('demo-member-a - '.$nextMonth.' - £0.96', $content);
        $this->assertStringContainsString('demo-member-b - '.$nextMonth.' - £0.96', $content);
        $this->assertStringContainsString('demo-member-c - '.$nextMonth.' - £0.96', $content);
        $this->assertStringNotContainsString('Savings in sight', $content);
    }

    public function test_dashboard_dead_links_point_at_real_urls(): void
    {
        $content = $this->actingAs($this->demo)->get('/en/dashboard')
            ->assertOk()->getContent();

        $this->assertStringContainsString('<a href="/en/my-bank-account" class="dash-sheet-link "', $content);
        $this->assertStringContainsString('href="https://help.sharesub.com/"', $content);
        $this->assertStringContainsString('<a href="/en/dashboard" class="dash-tabbar-item active">', $content);
    }

    public function test_dashboard_payments_this_month_lists_this_month_payout(): void
    {
        $content = $this->actingAs($this->demo)->get('/en/dashboard')
            ->assertOk()->getContent();

        $this->assertStringContainsString('Transfer made - '.now()->format('Y/m').'/09 - £2.88', $content);
        $this->assertStringNotContainsString("You've spent nothing", $content);
        // Coming soon now renders the pending next-month payments,
        // so the captured empty state is replaced for the demo user.
        $nextMonth = now()->startOfMonth()->addMonth()->format('Y/m/d');
        $this->assertStringContainsString('demo-member-a - '.$nextMonth.' - £0.96', $content);
        $this->assertStringNotContainsString('Savings in sight', $content);
    }

    public function test_dashboard_without_wallet_rows_keeps_captured_zeros(): void
    {
        $other = User::factory()->create(['role' => 'user', 'first_name' => 'Other']);

        $content = $this->actingAs($other)->get('/en/dashboard')
            ->assertOk()->getContent();

        $this->assertStringContainsString('<span class="stat-value">€0</span>', $content);
    }

    // Shared list ----------------------------------------------------------

    public function test_shared_list_create_button_and_discover_title(): void
    {
        $content = $this->actingAs($this->demo)->get('/en/my-shared-subscriptions')
            ->assertOk()->getContent();

        $this->assertStringContainsString('Create a shared Subscription', $content);
        $this->assertStringContainsString('Discover also', $content);
    }

    public function test_shared_list_row_uses_real_markup_and_db_data(): void
    {
        $content = $this->actingAs($this->demo)->get('/en/my-shared-subscriptions')
            ->assertOk()->getContent();

        $this->assertStringContainsString('<div class="subscriptions-item">', $content);
        $this->assertStringContainsString('<div class="subscriptions-item-name">Nordpass</div>', $content);
        $this->assertStringContainsString('<span class="pill pill-navy">Public</span>', $content);
        $this->assertStringContainsString('Created on '.now()->format('Y/m/d'), $content);
        // 5 shareable seats, 3 taken: 2 remaining.
        $this->assertStringContainsString('2 seats remaining', $content);
        $this->assertSame(1, preg_match('/<div class="subscriptions-item-content-users">(.*?)<\/div>/s', $content, $icons));
        $this->assertSame(5, substr_count($icons[1], '<svg'));
        $this->assertSame(3, substr_count($content, 'class="colored"'));
        // No stored total price: big price is 0.96 x 5 seats = 4.80.
        $this->assertStringContainsString('<span class="t3-bold">£4</span>.8', $content);
        $this->assertStringContainsString('Each subscriber pays you <strong>£0.96 / month</strong>', $content);
        $this->assertStringContainsString('href="/en/my-shared-subscriptions/'.$this->derivedHash($this->offer).'"', $content);
        $this->assertStringContainsString('href="/en/my-chat/subscription/'.$this->derivedHash($this->offer).'"', $content);
    }

    public function test_terminated_offer_is_hidden_until_the_toggle(): void
    {
        $this->actingAs($this->demo)->post(
            '/en/my-shared-subscriptions/'.$this->offer->id.'/end',
            ['confirmation' => 'Delete'],
        )->assertRedirect('/en/my-shared-subscriptions');

        $content = $this->actingAs($this->demo)->get('/en/my-shared-subscriptions')
            ->assertOk()->getContent();

        // Terminated rows carry data-canceled and start hidden, so the
        // captured #showInactive toggle can reveal them.
        $this->assertStringContainsString('data-canceled style="display: none;"', $content);
        $this->assertStringContainsString('id="showInactive"', $content);
    }

    // Detail page ----------------------------------------------------------

    public function test_detail_page_renders_header_stats_and_tabs(): void
    {
        $content = $this->actingAs($this->demo)->get('/en/my-shared-subscriptions/'.$this->offer->id)
            ->assertOk()->getContent();

        $this->assertStringContainsString('<title>Nordpass</title>', $content);
        $this->assertStringContainsString('href="/en/my-shared-subscriptions" class="dashboard-header-actions-back"', $content);
        $this->assertStringContainsString('<div class="stats-name mb-sm-2 mb-md-0">Nordpass</div>', $content);
        // Monthly income is the total price (0.96 x 5 = 4.80).
        $this->assertStringContainsString('<span class="t3-bold">£4</span>.8', $content);
        $this->assertStringContainsString('<div class="stats-blok-name">Revenue per subscriber</div>', $content);
        $this->assertStringContainsString('<span>£0.96</span>', $content);
        $this->assertStringContainsString('<div class="stats-blok-fraction">3<span>/<strong>5</strong></span></div>', $content);
        $this->assertStringContainsString('data-tab="#identities-tab" class="active"', $content);
        $this->assertStringContainsString('data-tab="#subscribers-tab"', $content);
        $this->assertStringContainsString('data-tab="#payments-tab"', $content);
        $this->assertStringContainsString('data-tab="#handler-tab"', $content);
        $this->assertStringContainsString('href="/en/my-chat/subscription/'.$this->derivedHash($this->offer).'" class="stats-chat"', $content);
    }

    public function test_detail_page_resolves_by_hash_and_rejects_other_owners_and_guests(): void
    {
        // Guests are sent to login (checked first: actingAs persists
        // for later requests in the same test).
        $response = $this->get('/en/my-shared-subscriptions/'.$this->offer->id);
        $response->assertStatus(302);
        $this->assertStringContainsString('/en/userlogin', (string) $response->headers->get('Location'));

        $this->offer->update(['hash' => 'abc1234']);

        $this->actingAs($this->demo)->get('/en/my-shared-subscriptions/abc1234')->assertOk();

        $other = User::factory()->create(['role' => 'user']);
        $this->actingAs($other)->get('/en/my-shared-subscriptions/abc1234')->assertNotFound();
        $this->actingAs($other)->get('/en/my-shared-subscriptions/'.$this->offer->id)->assertNotFound();
    }

    public function test_detail_subscribers_tab_shows_members_and_mark_as_sent(): void
    {
        $content = $this->actingAs($this->demo)->get('/en/my-shared-subscriptions/'.$this->offer->id)
            ->assertOk()->getContent();

        $this->assertStringContainsString('demo-member-a', $content);
        $this->assertStringContainsString('demo-member-a@example.com', $content);
        $this->assertStringContainsString('Previous subscribers', $content);
        $this->assertStringContainsString('demo-member-old', $content);

        $member = OfferMember::where('username', 'demo-member-a')->first();
        $this->assertNotNull($member->invite_sent_at);

        // The owner member has no invitation email and no sent flag, so
        // the mark-as-sent form is exercised on a fresh member row.
        $unsent = OfferMember::create([
            'offer_id' => $this->offer->id,
            'username' => 'demo-member-d',
            'initials' => 'DD',
            'joined_at' => now(),
            'status' => OfferMember::STATUS_CURRENT,
            'invitation_email' => 'demo-member-d@example.com',
            'invite_sent_at' => null,
        ]);

        $content = $this->actingAs($this->demo)->get('/en/my-shared-subscriptions/'.$this->offer->id)
            ->assertOk()->getContent();
        $this->assertStringContainsString('Mark as sent', $content);

        $this->actingAs($this->demo)->post(
            '/en/my-shared-subscriptions/'.$this->offer->id.'/mark-email-invite-sent/'.$unsent->id,
        )->assertRedirect('/en/my-shared-subscriptions/'.$this->derivedHash($this->offer).'#subscribers-tab');

        $this->assertNotNull($unsent->fresh()->invite_sent_at);
    }

    public function test_detail_login_details_save_writes_secured_description(): void
    {
        $this->actingAs($this->demo)->post(
            '/en/my-shared-subscriptions/'.$this->offer->id,
            [
                'secured_description_sharing_method' => '0',
                'individual_secured_description' => 'Demo sharing instructions for the group.',
            ],
        )->assertRedirect('/en/my-shared-subscriptions/'.$this->derivedHash($this->offer).'#identities-tab');

        $this->assertSame(
            ['Demo sharing instructions for the group.'],
            $this->offer->fresh()->secured_description,
        );

        $content = $this->actingAs($this->demo)->get('/en/my-shared-subscriptions/'.$this->offer->id)
            ->assertOk()->getContent();
        $this->assertStringContainsString('Demo sharing instructions for the group.', $content);
    }

    public function test_detail_payments_tab_pager_and_total(): void
    {
        // 3 paid rows for the current month plus 3 pending rows for
        // next month (the dashboard Coming soon source).
        $this->assertSame(6, OfferPayment::count());

        $content = $this->actingAs($this->demo)->get('/en/my-shared-subscriptions/'.$this->offer->id)
            ->assertOk()->getContent();

        $this->assertStringContainsString(now()->format('F Y'), $content);
        $this->assertStringContainsString('TOTAL: £2.88', $content);
        $this->assertStringContainsString('Paid - available end of period', $content);
        $this->assertStringContainsString('demo-member-b', $content);

        $previous = now()->startOfMonth()->subMonth();
        $content = $this->actingAs($this->demo)->get(
            '/en/my-shared-subscriptions/'.$this->offer->id.'?date='.$previous->format('Y-m-01').'#payments-tab',
        )->assertOk()->getContent();

        // No payment rows were seeded before the members joined.
        $this->assertStringContainsString($previous->format('F Y'), $content);
        $this->assertStringContainsString('TOTAL: £0', $content);
        $this->assertStringContainsString(
            '?date='.$previous->copy()->addMonth()->format('Y-m-01').'#payments-tab',
            $content,
        );
    }

    public function test_detail_migrate_changes_the_template_within_the_brand(): void
    {
        $brand = Brand::where('slug', 'nordpass')->first();
        $template = Template::create([
            'brand_id' => $brand->id,
            'name' => 'Nordpass Family',
            'slug' => 'nordpass-family',
            'seats' => 6,
            'shareable_seats' => 5,
            'available_seats' => 5,
            'cost_sharing' => 1,
            'credentials_type' => 0,
            'frequency' => 2,
            'price_with_fees' => 5.99,
            'retail_price' => 11.94,
            'currency' => 'GBP',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($this->demo)->post(
            '/en/my-shared-subscriptions/'.$this->offer->id.'/migrate',
            ['template_id' => $template->id],
        )->assertRedirect('/en/my-shared-subscriptions/'.$this->derivedHash($this->offer).'#handler-tab');

        $offer = $this->offer->fresh();
        $this->assertSame($template->id, $offer->template_id);
        $this->assertSame('Nordpass Family', $offer->name);
        $this->assertSame('11.94', $offer->total_price);
        $this->assertSame('1.99', $offer->price);
    }

    public function test_detail_migrate_rejects_a_template_from_another_brand(): void
    {
        $otherBrand = Brand::create([
            'name' => 'Other',
            'slug' => 'other',
            'price' => 1.00,
            'currency' => 'GBP',
            'is_active' => true,
        ]);
        $template = Template::create([
            'brand_id' => $otherBrand->id,
            'name' => 'Other Plan',
            'slug' => 'other-plan',
            'seats' => 2,
            'shareable_seats' => 1,
            'available_seats' => 1,
            'frequency' => 2,
            'price_with_fees' => 1.00,
            'currency' => 'GBP',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($this->demo)->post(
            '/en/my-shared-subscriptions/'.$this->offer->id.'/migrate',
            ['template_id' => $template->id],
        )->assertRedirect('/en/my-shared-subscriptions/'.$this->derivedHash($this->offer).'#handler-tab');

        $this->assertNull($this->offer->fresh()->template_id);
    }

    public function test_detail_end_requires_delete_and_terminates(): void
    {
        $this->actingAs($this->demo)->post(
            '/en/my-shared-subscriptions/'.$this->offer->id.'/end',
            ['confirmation' => 'delete'],
        )->assertRedirect('/en/my-shared-subscriptions/'.$this->derivedHash($this->offer).'#handler-tab');
        $this->assertSame('active', $this->offer->fresh()->status);

        $this->actingAs($this->demo)->post(
            '/en/my-shared-subscriptions/'.$this->offer->id.'/end',
            ['confirmation' => 'Delete'],
        )->assertRedirect('/en/my-shared-subscriptions');

        $offer = $this->offer->fresh();
        $this->assertSame('terminated', $offer->status);
        $this->assertFalse((bool) $offer->is_active);

        // A terminated offer keeps its detail page.
        $this->actingAs($this->demo)->get('/en/my-shared-subscriptions/'.$this->offer->id)->assertOk();
    }

    public function test_offer_payments_table_and_demo_rows(): void
    {
        $this->assertTrue(Schema::hasTable('offer_payments'));
        $this->assertTrue(Schema::hasColumns('offer_payments', [
            'id', 'offer_id', 'member_id', 'month', 'status', 'paid_at',
            'amount', 'currency', 'created_at', 'updated_at',
        ]));

        // Current month only: the members joined this month, so earlier
        // months get no rows.
        // 3 paid rows for the current month plus 3 pending rows for
        // next month (the dashboard Coming soon source).
        $this->assertSame(6, OfferPayment::count());
        $payment = OfferPayment::first();
        $this->assertSame('paid', $payment->status);
        $this->assertSame('0.96', $payment->amount);
        $this->assertSame('GBP', $payment->currency);
        $this->assertSame(now()->startOfMonth()->toDateString(), $payment->month->toDateString());
    }
}
