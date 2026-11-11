<?php

namespace App\Http\Controllers;

use App\Services\StripeConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CardsController extends Controller
{
    /**
     * Means of payment: the saved card list from payment_methods, with
     * set-default and delete actions posting exactly as the capture
     * does (hidden `card` identifier).
     *
     * Add-card flow: with a real Stripe publishable key, the page loads
     * Stripe.js and mounts a Card Element; with a real secret key as
     * well, the server creates a SetupIntent through the Stripe API and
     * the Element confirms it with stripe.confirmCardSetup. No raw card
     * data is ever collected or stored; only brand, last four digits,
     * expiry, holder name, the main flag and the Stripe reference.
     * There is no card save route, so a confirmed payment method cannot
     * be persisted yet; the page says so instead of pretending the card
     * was saved. With a placeholder or absent key, the create state
     * renders a notice with no inputs, exactly as before.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect('/en/admin/cards');
        }

        $cards = $user->paymentMethods()
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get();

        $showCreateNotice = $request->boolean('create_card');
        $publishableKey = config('services.stripe.key');
        $publishableReady = StripeConfig::isRealKey($publishableKey);
        $secretReady = StripeConfig::isRealKey(config('services.stripe.secret'));

        $clientSecret = null;

        // A SetupIntent can only be created with the secret key, so the
        // Element only gets a client secret when both keys are real.
        if ($showCreateNotice && $publishableReady && $secretReady) {
            $clientSecret = $this->createSetupIntent(['card'], $user->stripe_customer_id);
        }

        return view('money.cards', [
            'title' => 'My payment methods - Sharesub',
            'pageTitle' => 'My payment methods',
            'activeNav' => 'cards',
            'activeGroup' => 'payments',
            'user' => $user,
            'cards' => $cards,
            'showCreateNotice' => $showCreateNotice,
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

    public function setDefault(Request $request)
    {
        $validated = $request->validate([
            'card' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $card = $user->paymentMethods()->findOrFail($validated['card']);

        DB::transaction(function () use ($user, $card): void {
            $user->paymentMethods()->where('is_main', true)->update(['is_main' => false]);
            $card->update(['is_main' => true]);
        });

        return back()->with('status', 'Your main payment method has been updated.');
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'card' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $card = $user->paymentMethods()->findOrFail($validated['card']);
        $wasMain = (bool) $card->is_main;

        $card->delete();

        if ($wasMain) {
            $next = $user->paymentMethods()->orderBy('id')->first();
            if ($next !== null) {
                $next->update(['is_main' => true]);
            }
        }

        return back()->with('status', 'Payment method deleted.');
    }
}
