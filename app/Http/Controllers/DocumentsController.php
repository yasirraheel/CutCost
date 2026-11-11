<?php

namespace App\Http\Controllers;

use App\Models\KycSubmission;
use App\Models\User;
use App\Services\StripeConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DocumentsController extends Controller
{
    /**
     * My documents: the two verification status lines, rendered from the
     * local identity flow and the connected bank accounts.
     *
     * Stripe Connect onboarding: when a real Stripe secret key is
     * configured, the button opens a per-user Stripe Connect onboarding
     * URL, created through the Stripe API (the existing Connect account
     * id is reused when the user already has one). When the key is
     * missing, a placeholder, or the Stripe call fails, the button keeps
     * linking to the existing local identity flow and the page says so
     * plainly. The wording for pending, rejected and not started states
     * is not in the capture (this capture shows the verified state
     * only), so those words come from our identity page status labels.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect('/en/admin/account/identity');
        }

        $submission = $user->kycSubmission;
        $status = $submission?->status;

        $accountStatus = match ($status) {
            KycSubmission::STATUS_APPROVED => 'Verified',
            KycSubmission::STATUS_UNDER_REVIEW => 'Under review',
            KycSubmission::STATUS_REJECTED => 'Rejected',
            default => 'Not verified',
        };

        $hasBankAccount = $user->bankAccounts()->exists();
        $transfersStatus = $hasBankAccount ? 'Account added' : 'No bank account added';

        $secretReady = StripeConfig::isRealKey(config('services.stripe.secret'));
        $onboardingUrl = null;

        // A verified user has nothing to onboard, so no Stripe call is
        // made for them. The secret key alone drives this flow; the
        // publishable key is not needed to build an onboarding link.
        if ($secretReady && $status !== KycSubmission::STATUS_APPROVED) {
            $onboardingUrl = $this->stripeOnboardingUrl($user);
        }

        return view('money.documents', [
            'title' => 'Identity and Verification - Sharesub',
            'pageTitle' => 'Identity and Verification',
            'activeNav' => 'documents',
            'activeGroup' => null,
            'user' => $user,
            'accountStatus' => $accountStatus,
            'accountVerified' => $status === KycSubmission::STATUS_APPROVED,
            'transfersStatus' => $transfersStatus,
            'transfersReady' => $hasBankAccount,
            'stripeOnboardingUrl' => $onboardingUrl,
            'stripeSecretReady' => $secretReady,
        ]);
    }

    /**
     * Build a Stripe Connect onboarding link for the user through the
     * Stripe REST API. Returns null when anything fails; the page then
     * falls back to the local identity flow. The secret key is only
     * ever sent to Stripe over HTTPS, and is never logged or rendered.
     */
    private function stripeOnboardingUrl(User $user): ?string
    {
        $secret = config('services.stripe.secret');

        try {
            $accountId = $user->stripe_connect_account_id;

            if ($accountId === null || $accountId === '') {
                $payload = ['type' => 'express', 'email' => $user->email];

                if (is_string($user->country) && strlen($user->country) === 2) {
                    $payload['country'] = strtoupper($user->country);
                }

                $account = Http::timeout(10)
                    ->withBasicAuth($secret, '')
                    ->asForm()
                    ->post('https://api.stripe.com/v1/accounts', $payload);

                if (! $account->successful()) {
                    return null;
                }

                $accountId = $account->json('id');

                if (! is_string($accountId) || $accountId === '') {
                    return null;
                }

                $user->update(['stripe_connect_account_id' => $accountId]);
            }

            $link = Http::timeout(10)
                ->withBasicAuth($secret, '')
                ->asForm()
                ->post('https://api.stripe.com/v1/account_links', [
                    'account' => $accountId,
                    'refresh_url' => url('/en/my-documents'),
                    'return_url' => url('/en/my-documents'),
                    'type' => 'account_onboarding',
                ]);

            if (! $link->successful()) {
                return null;
            }

            $url = $link->json('url');

            return is_string($url) && $url !== '' ? $url : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
