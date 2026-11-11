@extends('layouts.dashboard')

@section('content')
    @php
        // Brand marks, drawn as simple inline marks. Only the brand, last
        // four digits, expiry and holder name are ever shown or stored.
        $brandIcons = [
            'visa' => '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="30" height="30" rx="4" fill="#1A1F71"/><text x="15" y="20" text-anchor="middle" font-family="Arial, sans-serif" font-size="9" font-weight="bold" font-style="italic" fill="#FFFFFF">VISA</text></svg>',
            'mastercard' => '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="15" r="9" fill="#EB001B"/><circle cx="18" cy="15" r="9" fill="#F79E1B" fill-opacity="0.85"/></svg>',
        ];
        $brandNames = [
            'visa' => 'Visa',
            'mastercard' => 'Mastercard/Eurocard',
        ];
    @endphp
    <div class="card-sharesub card-sharesub--roomy">
        @if (session('status'))
            <div class="alert alert-success mb-3" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger mb-3" role="alert">{{ $errors->first() }}</div>
        @endif
        @if ($showCreateNotice)
            @if ($stripeSetupIntentClientSecret)
                {{-- With real Stripe keys this is the live Card Element. It is a Stripe hosted field, not a plain input, so no raw card data is ever typed into or posted from this page. --}}
                <div id="card-element"></div>
                <div class="p3-regular text-dark-red mt-2" id="card-errors" role="alert"></div>
                <button type="button" id="card-button" class="btn-sharesub bleu mt-3">Confirm card</button>
                <div class="alert alert-success mt-3" id="card-success" role="status" style="display: none"></div>
            @else
                <div class="alert alert-info mb-3" role="status">Adding a payment method needs a Stripe connection, which is not set up yet. No card details are collected on this page. @if ($stripePublishableReady) The publishable key is set, but a real Stripe secret key is needed to add a card with Stripe. @else Real Stripe keys are needed to add a card with Stripe. @endif</div>
            @endif
        @endif
        @if ($cards->isEmpty())
            <div class="d-flex align-items-center">
                <img src="/img/dashboard/no-card-saved.png" class="mr-3" alt="">
                <span class="p1-semibold">
                    No card registered. </span>
            </div>
            <p>You must have at least one valid payment method if you have an active subscription. Adding a credit card will trigger a pre-authorization for a few cents, which will be refunded immediately.</p>
        @else
            <ul class="card-list mt-3">
                <p class="p2-bold text-dark mb-2">Main means of payment</p>
                @foreach ($cards as $card)
                    <li class="mb-4">
                        <div class="row no-gutters card-list-element">
                            <div class="col-2 col-sm-3 p3-regular sm:p2-bold">
                                {!! $brandIcons[strtolower($card->brand)] ?? '' !!}
                                <span class="d-none d-md-inline ml-2">{{ $brandNames[strtolower($card->brand)] ?? $card->brand }}</span>
                            </div>
                            <div class="col-6 p3-regular sm:p2-bold">
                                XXXX XXXX XXXX {{ $card->last4 }}
                            </div>
                            <div class="col-3 col-sm-2 p3-regular">
                                {{ $card->exp_month }} / {{ $card->exp_year }}
                            </div>
                            <div class="col-1 text-right">
                                <svg class="cursor-pointer" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M18.7371 8.32428C19.1103 8.7314 19.0828 9.36397 18.6757 9.73716L12.6757 15.2372C12.2934 15.5876 11.7066 15.5876 11.3243 15.2372L5.32427 9.73716C4.91715 9.36397 4.88965 8.7314 5.26284 8.32428C5.63603 7.91716 6.2686 7.88966 6.67572 8.26285L12 13.1434L17.3243 8.26285C17.7314 7.88966 18.364 7.91716 18.7371 8.32428Z" fill="#016DF4" />
                                </svg>
                            </div>
                        </div>
                        <div class="card-information-expand" style="display: none">
                            <div class="d-flex align-items-center flex-column flex-sm-row align-items-center">
                                <div>
                                    <div class="p3-regular text-grey-2">Name</div>
                                    <div>{{ $card->holder_name }}</div>
                                </div>
                                <div class="ml-sm-auto mt-2 mt-sm-0 d-flex align-items-center flex-column flex-sm-row">
                                    <form action="/en/my-cards/set-default" method="post" class="mb-3 mb-sm-0">
                                        @csrf
                                        <input type="hidden" name="card" value="{{ $card->id }}" />
                                        <button type="submit" class="btn-sharesub bleu mx-auto d-block">Make main</button>
                                    </form>
                                    <form action="/en/my-cards/delete" method="post">
                                        @csrf
                                        <input type="hidden" name="card" value="{{ $card->id }}" />
                                        <button type="submit" class="text-blue p2-semibold ml-2 bg-white border-0">
                                            <svg class="" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M6 19C6 20.1 6.9 21 8 21H16C17.1 21 18 20.1 18 19V9C18 7.9 17.1 7 16 7H8C6.9 7 6 7.9 6 9V19ZM18 4H15.5L14.79 3.29C14.61 3.11 14.35 3 14.09 3H9.91C9.65 3 9.39 3.11 9.21 3.29L8.5 4H6C5.45 4 5 4.45 5 5C5 5.55 5.45 6 6 6H18C18.55 6 19 5.55 19 5C19 4.45 18.55 4 18 4Z" fill="currentColor" />
                                            </svg>
                                            Delete </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
            <p>You must have at least one valid payment method if you have an active subscription. Adding a credit card will trigger a pre-authorization for a few cents, which will be refunded immediately.</p>
        @endif
        <div class="line-sep my-5"></div>
        <div class="text-center">
            <a href="/en/my-cards?create_card=1" class="btn-sharesub bleu mx-auto d-inline-block">Add a payment method</a>
        </div>
    </div>
@endsection

@if ($stripeSetupIntentClientSecret)
    @push('scripts')
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            (function () {
                var stripe = Stripe(@json($stripePublishableKey));
                var elements = stripe.elements();
                var card = elements.create('card');
                card.mount('#card-element');
                var button = document.getElementById('card-button');
                var errors = document.getElementById('card-errors');
                var success = document.getElementById('card-success');
                card.on('change', function (event) {
                    errors.textContent = event.error ? event.error.message : '';
                });
                button.addEventListener('click', function () {
                    button.disabled = true;
                    errors.textContent = '';
                    stripe.confirmCardSetup(@json($stripeSetupIntentClientSecret), {
                        payment_method: { card: card }
                    }).then(function (result) {
                        if (result.error) {
                            errors.textContent = result.error.message;
                            button.disabled = false;
                        } else {
                            success.textContent = 'Your card was confirmed with Stripe. Saving it to your payment methods needs the card save endpoint, which is not wired yet.';
                            success.style.display = 'block';
                            button.style.display = 'none';
                        }
                    });
                });
            })();
        </script>
    @endpush
@endif
