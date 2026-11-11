@extends('layouts.dashboard')

@section('content')
    <div class="card-sharesub card-sharesub--roomy">
        @if (session('status'))
            <div class="alert alert-success mb-3" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger mb-3" role="alert">{{ $errors->first() }}</div>
        @endif
        <p class="p2-regular text-grey-alt mb-4">
            Provide your bank account number (IBAN) to receive your transfers. The account must be in the same currency as the funds received in your wallet for the transfer to be executed. </p>
        @if (! $stripePublishableReady)
            <div class="alert alert-info mb-3" role="status">Stripe bank account setup needs real Stripe keys, which are not configured yet, so the IBAN field below stays empty. No bank details are collected on this page.</div>
        @endif
        <ul class="bank-list mb-5">
            @foreach ($accounts as $account)
                <li class="row mb-3">
                    <div class="col-md-10 col-12">
                        <div class="bank-list__item">
                            <div class="col">
                                <span class="text-dark-green mr-1"><svg width="16" height="16" viewBox="0 0 54 54" fill="currentColor" xmlns="http://www.w3.org/2000/svg" class="">
                                    <path d="M27 54C41.9117 54 54 41.9117 54 27C54 12.0883 41.9117 0 27 0C12.0883 0 0 12.0883 0 27C0 41.9117 12.0883 54 27 54Z" fill="currentColor"/>
                                    <path d="M13.5107 28.6875L24.2067 37.6875L41.658 18" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                </span>
                                <b>{{ $account->bank_name }}</b>
                            </div>
                            <div class="col text-right">
                                {{ $account->masked_reference }}
                            </div>
                            <div class="col-2 text-right">
                                {{ \App\Http\Controllers\BankAccountController::currencyLabel($account->currency) }}
                            </div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        <h3 class="p1-bold mb-2">Add a bank account</h3>
        <p class="p2-regular text-grey-alt mb-3">
            Sharesub recommends Revolut for receiving your winnings: it is currently the fastest bank for receiving your transfers. Get €20 free by following this <a href="#offline" target="_blank" rel="noopener">partner link</a>.
        </p>
        {{-- The IBAN field is a Stripe IBAN Element mount point, not a plain input, so no raw IBAN is ever typed into or posted from this page. The Element mounts only when a real Stripe publishable key is configured; the hidden stripe_token is filled by Stripe.js from the confirmed SetupIntent, never typed by the user. --}}
        <form method="POST" class="stripe-iban" id="iban-form" action="/en/my-bank-account/save">
            @csrf
            <input type="hidden" name="stripe_token" id="stripe_token" value="" />
            <input type="hidden" name="full_name" id="full_name" value="{{ trim($user->first_name.' '.$user->last_name) }}" />
            <div class="form-group">
                <div class="row">
                    <div class="col-12 col-md-2">
                        <div class="form-group">
                            <label for="iban_currency">Currency</label>
                            <select class="input-sharesub" id="iban_currency" name="currency">
                                <option value="gbp" selected>GBP £</option>
                                <option value="eur">EUR €</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-10">
                        <label for="iban-element">IBAN</label>
                        <div id="iban-element"></div>
                        <div class="p3-regular text-dark-red mt-2" id="iban-errors" role="alert"></div>
                    </div>
                </div>
            </div>
            <button id="iban-button" class="btn-sharesub bleu">Save</button>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://js.stripe.com/v3/"></script>
    @if ($stripePublishableKey)
        <script>
            (function () {
                var stripe = Stripe(@json($stripePublishableKey));
                var elements = stripe.elements();
                var iban = elements.create('iban', { supportedCountries: ['SEPA'] });
                iban.mount('#iban-element');
                var form = document.getElementById('iban-form');
                var errors = document.getElementById('iban-errors');
                var button = document.getElementById('iban-button');
                iban.on('change', function (event) {
                    errors.textContent = event.error ? event.error.message : '';
                });
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    @if ($stripeSetupIntentClientSecret)
                        button.disabled = true;
                        errors.textContent = '';
                        stripe.confirmSepaDebitSetup(@json($stripeSetupIntentClientSecret), {
                            payment_method: {
                                sepa_debit: iban,
                                billing_details: { name: document.getElementById('full_name').value }
                            }
                        }).then(function (result) {
                            if (result.error) {
                                errors.textContent = result.error.message;
                                button.disabled = false;
                            } else {
                                document.getElementById('stripe_token').value = result.setupIntent.payment_method;
                                form.submit();
                            }
                        });
                    @else
                        errors.textContent = 'A real Stripe secret key is needed to save this bank account.';
                    @endif
                });
            })();
        </script>
    @endif
@endpush
