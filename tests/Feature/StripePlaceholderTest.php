<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\StripeConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripePlaceholderTest extends TestCase
{
    use RefreshDatabase;

    private const PLACEHOLDER_KEY = 'pk_test_PLACEHOLDER_REPLACE_WITH_REAL_KEY';
    private const PLACEHOLDER_SECRET = 'sk_test_PLACEHOLDER_REPLACE_WITH_REAL_KEY';
    private const PLACEHOLDER_WEBHOOK_SECRET = 'whsec_PLACEHOLDER_REPLACE_WITH_REAL_KEY';

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

    private function usePlaceholderKeys(): void
    {
        config([
            'services.stripe.key' => self::PLACEHOLDER_KEY,
            'services.stripe.secret' => self::PLACEHOLDER_SECRET,
            'services.stripe.webhook_secret' => self::PLACEHOLDER_WEBHOOK_SECRET,
        ]);
    }

    public function test_placeholder_keys_are_read_from_config(): void
    {
        $this->usePlaceholderKeys();

        $this->assertSame(self::PLACEHOLDER_KEY, config('services.stripe.key'));
        $this->assertSame(self::PLACEHOLDER_SECRET, config('services.stripe.secret'));
        $this->assertSame(self::PLACEHOLDER_WEBHOOK_SECRET, config('services.stripe.webhook_secret'));
    }

    public function test_is_configured_is_false_with_placeholder_keys(): void
    {
        $this->usePlaceholderKeys();

        $this->assertFalse(StripeConfig::isConfigured());
    }

    public function test_is_configured_is_false_when_only_one_key_is_real(): void
    {
        config([
            'services.stripe.key' => 'pk_test_FAKEKEY123456',
            'services.stripe.secret' => self::PLACEHOLDER_SECRET,
        ]);

        $this->assertFalse(StripeConfig::isConfigured());

        config([
            'services.stripe.key' => self::PLACEHOLDER_KEY,
            'services.stripe.secret' => 'sk_test_FAKEKEY123456',
        ]);

        $this->assertFalse(StripeConfig::isConfigured());
    }

    public function test_is_configured_is_true_only_when_both_keys_are_real(): void
    {
        config([
            'services.stripe.key' => 'pk_test_FAKEKEY123456',
            'services.stripe.secret' => 'sk_test_FAKEKEY123456',
        ]);

        $this->assertTrue(StripeConfig::isConfigured());
    }

    public function test_is_real_key_rejects_placeholder_empty_and_null(): void
    {
        $this->assertFalse(StripeConfig::isRealKey(self::PLACEHOLDER_KEY));
        $this->assertFalse(StripeConfig::isRealKey(self::PLACEHOLDER_SECRET));
        $this->assertFalse(StripeConfig::isRealKey(''));
        $this->assertFalse(StripeConfig::isRealKey(null));
        $this->assertFalse(StripeConfig::isRealKey('pk_test_PLACEHOLDER'));
    }

    public function test_is_real_key_accepts_a_real_looking_key(): void
    {
        $this->assertTrue(StripeConfig::isRealKey('pk_test_FAKEKEY123456'));
        $this->assertTrue(StripeConfig::isRealKey('sk_test_FAKEKEY123456'));
        $this->assertTrue(StripeConfig::isRealKey('whsec_FAKEKEY123456'));
    }

    public function test_publishable_key_returns_null_in_placeholder_mode(): void
    {
        $this->usePlaceholderKeys();

        $this->assertNull(StripeConfig::publishableKey());
    }

    public function test_publishable_key_returns_the_key_when_both_keys_are_real(): void
    {
        config([
            'services.stripe.key' => 'pk_test_FAKEKEY123456',
            'services.stripe.secret' => 'sk_test_FAKEKEY123456',
        ]);

        $this->assertSame('pk_test_FAKEKEY123456', StripeConfig::publishableKey());
    }

    public function test_documents_page_renders_in_placeholder_mode_without_secret(): void
    {
        $this->usePlaceholderKeys();
        $user = $this->demoUser();

        $response = $this->actingAs($user)->get('/en/my-documents');

        $response->assertOk();
        $response->assertSee('Stripe onboarding needs real Stripe keys, which are not configured yet, so verification continues with the local identity flow.');
        $response->assertSee('/en/my-account/identity', false);
        $response->assertSee('Start the verification');
        $response->assertDontSee(self::PLACEHOLDER_SECRET, false);
        $response->assertDontSee('sk_test_', false);
        $response->assertDontSee(self::PLACEHOLDER_WEBHOOK_SECRET, false);
        $response->assertDontSee('whsec_', false);
    }

    public function test_cards_page_renders_create_notice_in_placeholder_mode_without_secret(): void
    {
        $this->usePlaceholderKeys();
        $user = $this->demoUser();
        PaymentMethod::query()->create([
            'user_id' => $user->id, 'brand' => 'Visa', 'last4' => '4242',
            'exp_month' => 12, 'exp_year' => 2028,
            'holder_name' => 'Demo User', 'is_main' => true, 'stripe_ref' => null,
        ]);

        $response = $this->actingAs($user)->get('/en/my-cards?create_card=1');

        $response->assertOk();
        $response->assertSee('Adding a payment method needs a Stripe connection, which is not set up yet. No card details are collected on this page.');
        $response->assertSee('Real Stripe keys are needed to add a card with Stripe.');
        $response->assertDontSee('id="card-element"', false);
        $response->assertDontSee('id="card-button"', false);
        $response->assertDontSee('confirmCardSetup', false);
        $response->assertDontSee('name="number"', false);
        $response->assertDontSee('name="card_number"', false);
        $response->assertDontSee(self::PLACEHOLDER_SECRET, false);
        $response->assertDontSee('sk_test_', false);
        $response->assertDontSee(self::PLACEHOLDER_KEY, false);
        $response->assertDontSee(self::PLACEHOLDER_WEBHOOK_SECRET, false);
    }

    public function test_bank_page_renders_needs_keys_notice_with_empty_iban_mount_in_placeholder_mode(): void
    {
        $this->usePlaceholderKeys();
        $user = $this->demoUser();
        BankAccount::query()->create([
            'user_id' => $user->id, 'bank_name' => 'Demo Bank',
            'masked_reference' => '****1234', 'currency' => 'GBP', 'stripe_ref' => null,
        ]);

        $response = $this->actingAs($user)->get('/en/my-bank-account');

        $response->assertOk();
        $response->assertSee('Stripe bank account setup needs real Stripe keys, which are not configured yet, so the IBAN field below stays empty. No bank details are collected on this page.');
        $response->assertSee('id="iban-element"', false);
        $response->assertSee('Demo Bank');
        $response->assertDontSee('iban.mount', false);
        $response->assertDontSee('confirmSepaDebitSetup', false);
        $response->assertDontSee('name="iban"', false);
        $response->assertDontSee(self::PLACEHOLDER_SECRET, false);
        $response->assertDontSee('sk_test_', false);
        $response->assertDontSee(self::PLACEHOLDER_KEY, false);
        $response->assertDontSee(self::PLACEHOLDER_WEBHOOK_SECRET, false);
    }
}
