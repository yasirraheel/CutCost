<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Brand;
use App\Models\ChatMessage;
use App\Models\Offer;
use App\Models\OfferMember;
use App\Models\PaymentMethod;
use App\Models\Payout;
use App\Models\User;
use App\Models\WalletBalance;
use Database\Seeders\RawPages2DemoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RawPages2DataTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_create_all_raw_pages_2_tables_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('offer_members'));
        $this->assertTrue(Schema::hasColumns('offer_members', [
            'id', 'offer_id', 'user_id', 'username', 'initials', 'joined_at',
            'left_at', 'status', 'invitation_email', 'invite_sent_at',
            'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('chat_messages'));
        $this->assertTrue(Schema::hasColumns('chat_messages', [
            'id', 'offer_id', 'member_id', 'sender_id', 'body', 'is_new',
            'is_read', 'is_notified', 'is_moderated', 'waiting_moderation',
            'admin_override', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('wallet_balances'));
        $this->assertTrue(Schema::hasColumns('wallet_balances', [
            'id', 'user_id', 'metric', 'currency', 'amount', 'available_on',
            'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('payouts'));
        $this->assertTrue(Schema::hasColumns('payouts', [
            'id', 'user_id', 'label', 'paid_at', 'amount', 'currency',
            'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('payment_methods'));
        $this->assertTrue(Schema::hasColumns('payment_methods', [
            'id', 'user_id', 'brand', 'last4', 'exp_month', 'exp_year',
            'holder_name', 'is_main', 'stripe_ref', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('bank_accounts'));
        $this->assertTrue(Schema::hasColumns('bank_accounts', [
            'id', 'user_id', 'bank_name', 'masked_reference', 'currency',
            'stripe_ref', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumn('users', 'stripe_connect_account_id'));
        $this->assertTrue(Schema::hasColumn('users', 'stripe_customer_id'));
    }

    public function test_models_have_fillable_fields_relations_and_defaults(): void
    {
        $demo = $this->demoUser();
        $brand = $this->makeBrand();
        $offer = Offer::create([
            'brand_id' => $brand->id,
            'user_id' => $demo->id,
            'title' => 'Nordpass',
            'price' => 0.96,
            'currency' => 'GBP',
            'status' => 'active',
            'is_active' => true,
        ]);

        $member = OfferMember::create([
            'offer_id' => $offer->id,
            'user_id' => $demo->id,
            'username' => 'demo-user',
            'initials' => 'DU',
            'joined_at' => now(),
            'status' => OfferMember::STATUS_CURRENT,
        ]);
        $this->assertSame('current', $member->status);
        $this->assertTrue($member->isCurrent());
        $this->assertFalse($member->isPrevious());
        $this->assertTrue($member->offer->is($offer));
        $this->assertTrue($member->user->is($demo));
        $this->assertSame(1, $offer->members()->count());
        $this->assertSame(1, $demo->offerMembers()->count());

        // Plaintext body in v1, defaults from the migration.
        $message = ChatMessage::create([
            'offer_id' => $offer->id,
            'member_id' => $member->id,
            'sender_id' => $demo->id,
            'body' => 'Hello, demo message.',
        ]);
        // Refresh so the column defaults from the migration are loaded.
        $message->refresh();
        $this->assertTrue($message->is_new);
        $this->assertFalse($message->is_read);
        $this->assertFalse($message->is_notified);
        $this->assertFalse($message->is_moderated);
        $this->assertSame(0, $message->waiting_moderation);
        $this->assertFalse($message->admin_override);
        $this->assertTrue($message->offer->is($offer));
        $this->assertTrue($message->member->is($member));
        $this->assertTrue($message->sender->is($demo));
        $this->assertSame(1, $member->chatMessages()->count());
        $this->assertSame(1, $offer->chatMessages()->count());
        $this->assertSame(1, $demo->sentChatMessages()->count());

        $balance = WalletBalance::create([
            'user_id' => $demo->id,
            'metric' => WalletBalance::METRIC_PENDING,
            'currency' => 'GBP',
            'amount' => 2.88,
        ]);
        $this->assertTrue($balance->user->is($demo));
        $this->assertSame(1, $demo->walletBalances()->count());

        $payout = Payout::create([
            'user_id' => $demo->id,
            'paid_at' => now()->toDateString(),
            'amount' => 2.88,
            'currency' => 'GBP',
        ]);
        // Refresh so the label default from the migration is loaded.
        $payout->refresh();
        $this->assertSame('Transfer made', $payout->label);
        $this->assertTrue($payout->user->is($demo));
        $this->assertSame(1, $demo->payouts()->count());

        $card = PaymentMethod::create([
            'user_id' => $demo->id,
            'brand' => 'Visa',
            'last4' => '4242',
            'exp_month' => 12,
            'exp_year' => 2028,
            'holder_name' => 'Demo User',
            'is_main' => true,
        ]);
        $this->assertTrue($card->user->is($demo));
        $this->assertSame(1, $demo->paymentMethods()->count());

        $bank = BankAccount::create([
            'user_id' => $demo->id,
            'bank_name' => 'Demo Bank',
            'masked_reference' => '****1234',
            'currency' => 'GBP',
        ]);
        $this->assertTrue($bank->user->is($demo));
        $this->assertSame(1, $demo->bankAccounts()->count());

        $demo->update([
            'stripe_connect_account_id' => 'acct_demo',
            'stripe_customer_id' => 'cus_demo',
        ]);
        $this->assertContains('stripe_connect_account_id', (new User)->getFillable());
        $this->assertContains('stripe_customer_id', (new User)->getFillable());
        $this->assertSame('acct_demo', $demo->fresh()->stripe_connect_account_id);
        $this->assertSame('cus_demo', $demo->fresh()->stripe_customer_id);
    }

    public function test_wallet_balance_is_unique_per_user_metric_and_currency(): void
    {
        $demo = $this->demoUser();
        WalletBalance::create([
            'user_id' => $demo->id,
            'metric' => WalletBalance::METRIC_PENDING,
            'currency' => 'GBP',
            'amount' => 1.00,
        ]);

        $this->expectException(QueryException::class);
        WalletBalance::create([
            'user_id' => $demo->id,
            'metric' => WalletBalance::METRIC_PENDING,
            'currency' => 'GBP',
            'amount' => 2.00,
        ]);
    }

    public function test_seeder_creates_demo_rows_and_is_idempotent(): void
    {
        $demo = $this->demoUser();
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'first_name' => 'Demo',
            'last_name' => 'Admin',
            'username' => 'demo-admin',
            'role' => 'admin',
        ]);
        $brand = $this->makeBrand();
        Offer::create([
            'brand_id' => $brand->id,
            'user_id' => $demo->id,
            'title' => 'Nordpass',
            'price' => 0.96,
            'currency' => 'GBP',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->seed(RawPages2DemoSeeder::class);
        $this->assertDemoRows($demo, $admin);

        // Second run must create zero duplicates.
        $this->seed(RawPages2DemoSeeder::class);
        $this->assertDemoRows($demo, $admin);
    }

    public function test_seeder_does_nothing_without_the_demo_user(): void
    {
        $this->seed(RawPages2DemoSeeder::class);

        $this->assertSame(0, OfferMember::count());
        $this->assertSame(0, ChatMessage::count());
        $this->assertSame(0, WalletBalance::count());
        $this->assertSame(0, Payout::count());
        $this->assertSame(0, PaymentMethod::count());
        $this->assertSame(0, BankAccount::count());
    }

    private function assertDemoRows(User $demo, User $admin): void
    {
        // One active demo offer at 0.96 with 3 current co-subscribers,
        // so the monthly income base is 0.96 x 3 = 2.88.
        $this->assertSame(5, OfferMember::count());
        $this->assertSame(4, OfferMember::where('status', 'current')->count());
        $this->assertSame(1, OfferMember::where('status', 'previous')->count());
        $this->assertSame(
            ['demo-member-a', 'demo-member-b', 'demo-member-c', 'demo-member-old', 'demo-user'],
            OfferMember::orderBy('username')->pluck('username')->all(),
        );
        $owner = OfferMember::where('username', 'demo-user')->first();
        $this->assertNotNull($owner);
        $this->assertTrue($owner->user->is($demo));
        $this->assertSame('DU', $owner->initials);
        $previous = OfferMember::where('username', 'demo-member-old')->first();
        $this->assertNotNull($previous);
        $this->assertNotNull($previous->left_at);

        $this->assertSame(3, ChatMessage::count());
        $thread = OfferMember::where('username', 'demo-member-a')->first();
        $this->assertNotNull($thread);
        $this->assertSame(3, $thread->chatMessages()->count());
        $this->assertSame(1, ChatMessage::where('is_new', true)->count());
        $this->assertSame(
            'Hi, thanks for sharing this subscription with me.',
            ChatMessage::orderBy('id')->first()->body,
        );

        $this->assertSame(3, WalletBalance::count());
        $pending = WalletBalance::where('metric', 'pending')->first();
        $this->assertNotNull($pending);
        $this->assertSame('2.88', $pending->amount);
        $this->assertSame('GBP', $pending->currency);
        $available = WalletBalance::where('metric', 'available')->first();
        $this->assertNotNull($available);
        $this->assertSame('2.88', $available->amount);
        $this->assertSame(
            now()->startOfMonth()->addMonth()->addDays(8)->toDateString(),
            $available->available_on->toDateString(),
        );
        $earnings = WalletBalance::where('metric', 'total_earnings')->first();
        $this->assertNotNull($earnings);
        $this->assertSame('2.88', $earnings->amount);

        $this->assertSame(3, Payout::count());
        $this->assertSame(3, Payout::where('label', 'Transfer made')->where('currency', 'GBP')->count());
        foreach (Payout::all() as $payout) {
            $this->assertSame('2.88', $payout->amount);
            $this->assertSame(9, (int) $payout->paid_at->format('j'));
            $this->assertSame((int) now()->format('Y'), (int) $payout->paid_at->format('Y'));
        }

        // Demo user gets a card and a bank account; the admin user has
        // no offers, so the admin gets only those two rows.
        $this->assertSame(2, PaymentMethod::count());
        $demoCard = PaymentMethod::where('user_id', $demo->id)->first();
        $this->assertNotNull($demoCard);
        $this->assertSame('Visa', $demoCard->brand);
        $this->assertSame('4242', $demoCard->last4);
        $this->assertTrue($demoCard->is_main);
        $this->assertSame(1, PaymentMethod::where('user_id', $admin->id)->count());

        $this->assertSame(2, BankAccount::count());
        $demoBank = BankAccount::where('user_id', $demo->id)->first();
        $this->assertNotNull($demoBank);
        $this->assertSame('Demo Bank', $demoBank->bank_name);
        $this->assertSame('****1234', $demoBank->masked_reference);
        $this->assertSame('GBP', $demoBank->currency);
        $this->assertSame(1, BankAccount::where('user_id', $admin->id)->count());

        $this->assertSame(0, WalletBalance::where('user_id', $admin->id)->count());
        $this->assertSame(0, Payout::where('user_id', $admin->id)->count());
    }

    private function demoUser(): User
    {
        return User::factory()->create([
            'email' => 'demo@example.com',
            'first_name' => 'Demo',
            'last_name' => 'User',
            'username' => 'demo-user',
            'role' => 'user',
        ]);
    }

    private function makeBrand(): Brand
    {
        return Brand::create([
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
    }
}
