<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Services\StripeConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class BankAccountController extends Controller
{
    /**
     * Connected bank account: the connected list from bank_accounts plus
     * the add form with the Stripe IBAN Element mount point.
     *
     * With a real Stripe publishable key the page loads Stripe.js and
     * mounts the IBAN Element on #iban-element; with a real secret key
     * as well, the server creates a SetupIntent through the Stripe API
     * and the Element confirms it with stripe.confirmSepaDebitSetup,
     * after which the returned payment method id is posted as
     * stripe_token to the existing save endpoint. A full IBAN is never
     * stored: only the bank name, a masked reference and the currency,
     * plus the Stripe reference. With a placeholder or absent key the
     * mount stays empty and the page says plainly that real Stripe keys
     * are needed.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        $accounts = $user->bankAccounts()->orderBy('id')->get();

        $publishableKey = config('services.stripe.key');
        $publishableReady = StripeConfig::isRealKey($publishableKey);
        $secretReady = StripeConfig::isRealKey(config('services.stripe.secret'));

        $clientSecret = null;

        // A SetupIntent can only be created with the secret key, so the
        // Element only gets a client secret when both keys are real.
        if ($publishableReady && $secretReady) {
            $clientSecret = $this->createSetupIntent(['sepa_debit'], $user->stripe_customer_id);
        }

        return view('money.bank', [
            'title' => 'My Bank Account - Sharesub',
            'pageTitle' => 'My Bank Account',
            'activeNav' => 'bank',
            'activeGroup' => 'payments',
            'user' => $user,
            'accounts' => $accounts,
            'stripePublishableKey' => $publishableReady ? $publishableKey : null,
            'stripePublishableReady' => $publishableReady,
            'stripeSetupIntentClientSecret' => $clientSecret,
        ]);
    }

    /**
     * Create a SetupIntent through the Stripe REST API and return its
     * client secret, or null when it cannot be created. The secret key
     * is only ever sent to Stripe over HTTPS, and is never logged or
     * rendered. No Stripe PHP SDK is installed, so this uses a plain
     * HTTPS call instead.
     */
    private function createSetupIntent(array $paymentMethodTypes, ?string $customerId): ?string
    {
        $secret = config('services.stripe.secret');

        try {
            $payload = ['payment_method_types' => $paymentMethodTypes];

            if ($customerId !== null && $customerId !== '') {
                $payload['customer'] = $customerId;
            }

            $response = Http::timeout(10)
                ->withBasicAuth($secret, '')
                ->asForm()
                ->post('https://api.stripe.com/v1/setup_intents', $payload);

            if (! $response->successful()) {
                return null;
            }

            $clientSecret = $response->json('client_secret');

            return is_string($clientSecret) && $clientSecret !== '' ? $clientSecret : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'stripe_token' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', Rule::in(['gbp', 'eur', 'GBP', 'EUR'])],
            'full_name' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'masked_reference' => ['nullable', 'string', 'max:24', function ($attribute, $value, $fail): void {
                // A full IBAN must never be stored. Reject anything
                // shaped like one; only a masked reference is accepted.
                $compact = strtoupper(str_replace(' ', '', (string) $value));
                if (preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $compact) === 1) {
                    $fail('Enter only the masked reference, never a full IBAN.');
                }
            }],
        ]);

        $currency = strtoupper($validated['currency']);

        // Until Stripe token resolution exists server side, the bank name
        // and masked reference come from the request when the caller has
        // them, and fall back to neutral placeholders otherwise.
        $request->user()->bankAccounts()->updateOrCreate(
            ['currency' => $currency],
            [
                'bank_name' => $validated['bank_name'] ?? 'Bank account',
                'masked_reference' => $validated['masked_reference'] ?? '****',
                'stripe_ref' => $validated['stripe_token'],
            ],
        );

        return redirect('/en/my-bank-account')->with('status', 'Bank account saved.');
    }

    public static function currencyLabel(string $currency): string
    {
        $code = strtoupper($currency);
        $symbol = Currency::symbolFor($code);

        return $symbol !== null ? $code.' ('.$symbol.')' : $code;
    }
}
