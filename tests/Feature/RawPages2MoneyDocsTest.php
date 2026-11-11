<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\KycSubmission;
use App\Models\PaymentMethod;
use App\Models\Payout;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\WalletBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RawPages2MoneyDocsTest extends TestCase
{
    use RefreshDatabase;

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

    private function seedMoney(User $user): void
    {
        WalletBalance::query()->create([
            'user_id' => $user->id, 'metric' => WalletBalance::METRIC_PENDING,
            'currency' => 'GBP', 'amount' => 2.88, 'available_on' => null,
        ]);
        WalletBalance::query()->create([
            'user_id' => $user->id, 'metric' => WalletBalance::METRIC_AVAILABLE,
            'currency' => 'GBP', 'amount' => 2.88, 'available_on' => '2026-12-09',
        ]);
        WalletBalance::query()->create([
            'user_id' => $user->id, 'metric' => WalletBalance::METRIC_TOTAL_EARNINGS,
            'currency' => 'GBP', 'amount' => 2.88, 'available_on' => null,
        ]);

        foreach (['2026-09-09', '2026-10-09', '2026-11-09'] as $paidAt) {
            Payout::query()->create([
                'user_id' => $user->id, 'label' => 'Transfer made',
                'paid_at' => $paidAt, 'amount' => 2.88, 'currency' => 'GBP',
            ]);
        }

        PaymentMethod::query()->create([
            'user_id' => $user->id, 'brand' => 'Visa', 'last4' => '4242',
            'exp_month' => 12, 'exp_year' => 2028,
            'holder_name' => 'Demo User', 'is_main' => true, 'stripe_ref' => null,
        ]);

        BankAccount::query()->create([
            'user_id' => $user->id, 'bank_name' => 'Demo Bank',
            'masked_reference' => '****1234', 'currency' => 'GBP', 'stripe_ref' => null,
        ]);
    }

    public function test_guests_are_redirected_to_login_on_all_four_pages(): void
    {
        foreach (['/en/my-documents', '/en/my-wallet', '/en/my-cards', '/en/my-bank-account'] as $path) {
            $this->get($path)->assertRedirect('/en/userlogin');
        }
    }

    public function test_documents_page_shows_not_verified_and_bank_added_for_demo_user(): void
    {
        $user = $this->demoUser();
        $this->seedMoney($user);

        $response = $this->actingAs($user)->get('/en/my-documents');

        $response->assertOk();
        $response->assertSee('Verification');
        $response->assertSee('Account status:');
        $response->assertSee('Not verified');
        $response->assertSee('Account added');
        $response->assertSee('Start the verification');
        $response->assertSee('/en/my-account/identity', false);
        $response->assertSee('My Account Information');
        // The raw capture marks no sidebar link active on this page.
        $response->assertDontSee('navi_dash-link active', false);
    }

    public function test_documents_page_shows_verified_when_kyc_approved(): void
    {
        $user = $this->demoUser();
        $this->seedMoney($user);
        KycSubmission::query()->create([
            'user_id' => $user->id,
            'front_path' => 'kyc/1/front.png',
            'back_path' => 'kyc/1/back.png',
            'status' => KycSubmission::STATUS_APPROVED,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/en/my-documents');

        $response->assertOk();
        $response->assertSee('Verified');
    }

    public function test_documents_page_shows_no_bank_when_none_connected(): void
    {
        $user = $this->demoUser();

        $response = $this->actingAs($user)->get('/en/my-documents');

        $response->assertOk();
        $response->assertSee('No bank account added');
    }

    public function test_wallet_page_renders_balances_policy_and_payout_history(): void
    {
        $user = $this->demoUser();
        $this->seedMoney($user);
        SiteSetting::query()->create([
            'key' => 'wallet_policy_1',
            'value' => 'Available funds are automatically transferred to your account on the 9th of each month, as soon as your balance exceeds the minimum of €10. Transfers may take 3 to 5 days to reach your account, depending on your bank.',
        ]);

        $response = $this->actingAs($user)->get('/en/my-wallet');

        $response->assertOk();
        $response->assertSee('£2.88');
        $response->assertSee('Pending Amount');
        $response->assertSee('Available for transfer on 09/12');
        $response->assertSee('Total earnings');
        $response->assertSee('Previous transfers to your account');
        $response->assertSee('Transfer made');
        $response->assertSee('2026/11/09');
        $response->assertSee('2026/09/09');
        $response->assertSee('are automatically transferred to your account on the 9th of each month');
        // Demo user has no approved identity, so the verify banner shows.
        $response->assertSee('Verify your identity');
        $response->assertSee('/en/my-bank-account', false);
    }

    public function test_wallet_verify_banner_hides_when_verified_and_bank_connected(): void
    {
        $user = $this->demoUser();
        $this->seedMoney($user);
        KycSubmission::query()->create([
            'user_id' => $user->id,
            'front_path' => 'kyc/1/front.png',
            'back_path' => 'kyc/1/back.png',
            'status' => KycSubmission::STATUS_APPROVED,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/en/my-wallet');

        $response->assertOk();
        $response->assertDontSee('Verify your identity');
    }

    public function test_wallet_year_filter_limits_payout_rows(): void
    {
        $user = $this->demoUser();
        $this->seedMoney($user);
        Payout::query()->create([
            'user_id' => $user->id, 'label' => 'Transfer made',
            'paid_at' => '2025-11-09', 'amount' => 1.50, 'currency' => 'GBP',
        ]);

        $response = $this->actingAs($user)->get('/en/my-wallet?year=2025');

        $response->assertOk();
        $response->assertSee('2025/11/09');
        $response->assertSee('£1.5');
        $response->assertDontSee('2026/11/09');
        $response->assertSee('<option value="2026"', false);
        $response->assertSee('<option value="2025" selected', false);
    }

    public function test_wallet_renders_eur_line_when_eur_balance_exists(): void
    {
        $user = $this->demoUser();
        $this->seedMoney($user);
        WalletBalance::query()->create([
            'user_id' => $user->id, 'metric' => WalletBalance::METRIC_PENDING,
            'currency' => 'EUR', 'amount' => 5.00, 'available_on' => null,
        ]);

        $response = $this->actingAs($user)->get('/en/my-wallet');

        $response->assertOk();
        $response->assertSee('€5');
    }

    public function test_cards_page_lists_saved_card_with_actions(): void
    {
        $user = $this->demoUser();
        $this->seedMoney($user);

        $response = $this->actingAs($user)->get('/en/my-cards');

        $response->assertOk();
        $response->assertSee('Main means of payment');
        $response->assertSee('XXXX XXXX XXXX 4242');
        $response->assertSee('12 / 2028');
        $response->assertSee('Demo User');
        $response->assertSee('Make main');
        $response->assertSee('Delete');
        $response->assertSee('/en/my-cards/set-default', false);
        $response->assertSee('/en/my-cards/delete', false);
        $response->assertSee('/en/my-cards?create_card=1', false);
        $response->assertDontSee('No card registered.');
    }

    public function test_cards_page_shows_empty_state_when_no_cards(): void
    {
        $user = $this->demoUser();

        $response = $this->actingAs($user)->get('/en/my-cards');

        $response->assertOk();
        $response->assertSee('No card registered.');
        $response->assertSee('You must have at least one valid payment method');
    }

    public function test_cards_create_notice_renders_without_any_card_inputs(): void
    {
        $user = $this->demoUser();
        $this->seedMoney($user);

        $response = $this->actingAs($user)->get('/en/my-cards?create_card=1');

        $response->assertOk();
        $response->assertSee('needs a Stripe connection');
        $response->assertDontSee('name="number"', false);
        $response->assertDontSee('name="card_number"', false);
    }

    public function test_cards_set_default_switches_main_card(): void
    {
        $user = $this->demoUser();
        $this->seedMoney($user);
        $second = PaymentMethod::query()->create([
            'user_id' => $user->id, 'brand' => 'Mastercard', 'last4' => '5555',
            'exp_month' => 3, 'exp_year' => 2027,
            'holder_name' => 'Demo User', 'is_main' => false, 'stripe_ref' => null,
        ]);

        $response = $this->actingAs($user)->post('/en/my-cards/set-default', ['card' => $second->id]);

        $response->assertRedirect();
        $this->assertTrue($second->fresh()->is_main);
        $this->assertFalse(PaymentMethod::query()->where('last4', '4242')->first()->is_main);
    }

    public function test_cards_set_default_rejects_another_users_card(): void
    {
        $user = $this->demoUser();
        $other = User::factory()->create(['role' => 'user']);
        $card = PaymentMethod::query()->create([
            'user_id' => $other->id, 'brand' => 'Visa', 'last4' => '1111',
            'exp_month' => 1, 'exp_year' => 2030,
            'holder_name' => 'Other User', 'is_main' => false, 'stripe_ref' => null,
        ]);

        $this->actingAs($user)->post('/en/my-cards/set-default', ['card' => $card->id])
            ->assertNotFound();
    }

    public function test_cards_delete_removes_card_and_promotes_next_main(): void
    {
        $user = $this->demoUser();
        $this->seedMoney($user);
        $second = PaymentMethod::query()->create([
            'user_id' => $user->id, 'brand' => 'Mastercard', 'last4' => '5555',
            'exp_month' => 3, 'exp_year' => 2027,
            'holder_name' => 'Demo User', 'is_main' => false, 'stripe_ref' => null,
        ]);
        $main = PaymentMethod::query()->where('last4', '4242')->first();

        $response = $this->actingAs($user)->post('/en/my-cards/delete', ['card' => $main->id]);

        $response->assertRedirect();
        $this->assertNull(PaymentMethod::query()->find($main->id));
        $this->assertTrue($second->fresh()->is_main);
    }

    public function test_bank_page_lists_connected_account_and_iban_mount(): void
    {
        $user = $this->demoUser();
        $this->seedMoney($user);

        $response = $this->actingAs($user)->get('/en/my-bank-account');

        $response->assertOk();
        $response->assertSee('Demo Bank');
        $response->assertSee('****1234');
        $response->assertSee('GBP (£)');
        $response->assertSee('Add a bank account');
        $response->assertSee('id="iban-element"', false);
        $response->assertSee('id="iban-form"', false);
        $response->assertSee('/en/my-bank-account/save', false);
        $response->assertSee('https://js.stripe.com/v3/', false);
        $response->assertDontSee('name="iban"', false);
    }

    public function test_bank_save_with_token_stores_masked_reference_only(): void
    {
        $user = $this->demoUser();

        $response = $this->actingAs($user)->post('/en/my-bank-account/save', [
            'stripe_token' => 'btok_test123',
            'currency' => 'eur',
            'full_name' => 'Demo User',
            'bank_name' => 'Demo Euro Bank',
            'masked_reference' => '****5678',
        ]);

        $response->assertRedirect('/en/my-bank-account');
        $account = BankAccount::query()->where('user_id', $user->id)->where('currency', 'EUR')->first();
        $this->assertNotNull($account);
        $this->assertSame('Demo Euro Bank', $account->bank_name);
        $this->assertSame('****5678', $account->masked_reference);
        $this->assertSame('btok_test123', $account->stripe_ref);
    }

    public function test_bank_save_rejects_missing_token_and_bad_currency(): void
    {
        $user = $this->demoUser();

        $this->actingAs($user)->post('/en/my-bank-account/save', [
            'currency' => 'gbp',
        ])->assertSessionHasErrors('stripe_token');

        $this->actingAs($user)->post('/en/my-bank-account/save', [
            'stripe_token' => 'btok_test123',
            'currency' => 'usd',
        ])->assertSessionHasErrors('currency');

        $this->assertSame(0, BankAccount::query()->where('user_id', $user->id)->count());
    }

    public function test_bank_save_rejects_a_full_iban_as_masked_reference(): void
    {
        $user = $this->demoUser();

        $this->actingAs($user)->post('/en/my-bank-account/save', [
            'stripe_token' => 'btok_test123',
            'currency' => 'gbp',
            'bank_name' => 'Demo Bank',
            'masked_reference' => 'GB29NWBK60161331926819',
        ])->assertSessionHasErrors('masked_reference');

        $this->assertSame(0, BankAccount::query()->where('user_id', $user->id)->count());
    }

    public function test_admin_users_are_redirected_to_admin_sections(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/en/my-wallet')->assertRedirect('/en/admin/wallet');
        $this->actingAs($admin)->get('/en/my-cards')->assertRedirect('/en/admin/cards');
        $this->actingAs($admin)->get('/en/my-documents')->assertRedirect('/en/admin/account/identity');
    }

    public function test_legacy_html_urls_redirect_to_clean_urls(): void
    {
        $user = $this->demoUser();

        $this->actingAs($user)->get('/en/my-wallet.html')->assertRedirect('/en/my-wallet');
        $this->actingAs($user)->get('/en/my-cards.html')->assertRedirect('/en/my-cards');
        $this->actingAs($user)->get('/en/my-documents.html')->assertRedirect('/en/my-documents');
        $this->actingAs($user)->get('/en/my-bank-account.html')->assertRedirect('/en/my-bank-account');
    }
}
