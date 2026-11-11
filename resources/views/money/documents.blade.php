@extends('layouts.dashboard')

@section('content')
    <div class="px-xs-0">
        <div class="card-sharesub pt-none">
            <div class="row justify-content-center mb-3 mx--2">
                <a href="/en/my-account" class="col-6 col-md-4 p-2 tab-navigation">
                    <div>
                        My Account Information </div>
                </a>
                <a href="/en/my-documents" class="col-6 col-md-4 p-2 tab-navigation current">
                    Identity            </a>
            </div>
            <div class="mb-5">
                <h2 class="text-dark t6-bold mb-3">Verification</h2>
                <p>
                    Account status: <span @if ($accountVerified) class="text-success" @endif>{{ $accountStatus }}</span>
                </p>
                <p>
                    Transfers : <span @if ($transfersReady) class="text-success" @endif>{{ $transfersStatus }}</span>
                </p>
                <p>
                    Upload the required documents by editing the "Personal Information" section </p>
                <div class="row mb-3">
                    <div class="col col-md-6 offset-md-3">
                        @if ($stripeOnboardingUrl)
                            {{-- With real Stripe keys this opens the per-user Stripe Connect onboarding URL built by the controller. --}}
                            <a class="btn-sharesub bleu" href="{{ $stripeOnboardingUrl }}">Start the verification</a>
                            <p>Or continue with the <a href="/en/my-account/identity">local identity flow</a>.</p>
                        @else
                            {{-- Recorded deviation: the original links out to a per-user Stripe Connect onboarding URL. With real Stripe keys the button above uses that URL; without them this links to our existing local identity flow instead. --}}
                            <a class="btn-sharesub bleu" href="/en/my-account/identity">Start the verification</a>
                            @if ($stripeSecretReady)
                                <p>Stripe onboarding could not be started, so verification continues with the local identity flow.</p>
                            @else
                                <p>Stripe onboarding needs real Stripe keys, which are not configured yet, so verification continues with the local identity flow.</p>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
