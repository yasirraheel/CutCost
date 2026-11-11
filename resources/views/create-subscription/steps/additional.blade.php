@php
    $credentialsType = $offer->template?->credentials_type ?? 0;
    $hasCredentials = $credentialsType !== 0;
    $brandName = $offer->brand?->name ?? $offer->template?->name ?? $offer->name;
    $securedMode = old('secured_mode', 'shared');
@endphp

<form method="POST" action="{{ route('user.create-subscription.store') }}">
    @csrf

    <div class="subscription-information">
        <p class="subscription-information-title">Share your login details</p>

        <p class="subscription-information-description">Vous avez besoin de l'adresse mail de votre co-abonné pour lui partager {{ $brandName }}. You can check out our dedicated tutorial.</p>

        @if ($credentialsType === 6)
            <p class="subscription-information-description">This information is not visible to your subscribers, but is used for moderation and control purposes only. You must add your subscribers by invitation, as described above.</p>
        @endif

        <div class="row">
            @if ($credentialsType === 1)
                <div class="col-12 col-md-6 subscription-information-form">
                    <label for="credentials_login">Login</label>
                    <input type="text" name="credentials_login" id="credentials_login" value="{{ old('credentials_login') }}" class="input-sharesub" placeholder="Login" required>
                </div>
                <div class="col-12 col-md-6 subscription-information-form">
                    <label for="credentials_password">Password</label>
                    <input type="text" name="credentials_password" id="credentials_password" value="{{ old('credentials_password') }}" class="input-sharesub" placeholder="Your password for the service" required>
                </div>
            @elseif ($credentialsType === 2 || $credentialsType === 6)
                <div class="col-12 col-md-6 subscription-information-form">
                    <label for="credentials_email">Login (email)</label>
                    <input type="email" name="credentials_email" id="credentials_email" value="{{ old('credentials_email') }}" class="input-sharesub" placeholder="example@email.com" required>
                </div>
                <div class="col-12 col-md-6 subscription-information-form">
                    <label for="credentials_password">Password</label>
                    <input type="text" name="credentials_password" id="credentials_password" value="{{ old('credentials_password') }}" class="input-sharesub" placeholder="Your password for the service" required>
                </div>
            @elseif ($credentialsType === 3)
                <div class="col-12 col-md-6 subscription-information-form">
                    <label for="link_placeholder">Invite link</label>
                    <input type="text" name="credentials_link" id="link_placeholder" value="{{ old('credentials_link') }}" class="input-sharesub" placeholder="https://..." required>
                </div>
            @elseif ($credentialsType === 4)
                <div class="col-12 col-md-6 subscription-information-form">
                    <label for="link_placeholder">Invite link</label>
                    <input type="text" name="credentials_link" id="link_placeholder" value="{{ old('credentials_link') }}" class="input-sharesub" placeholder="https://..." required>
                </div>
                <div class="col-12 col-md-6 subscription-information-form">
                    <label for="address_placeholder">Address</label>
                    <input type="text" name="credentials_address" id="address_placeholder" value="{{ old('credentials_address') }}" class="input-sharesub" placeholder="Your postal address" required>
                </div>
            @endif
        </div>

        <div class="col-12 subscription-information-message">
            <div class="subscription-information-radios">
                <div class="subscription-information-radio">
                    <input type="radio" name="secured_mode" value="shared" {{ $securedMode === 'shared' ? 'checked' : '' }}>
                    <span>Same message for all subscribers</span>
                </div>
                <div class="subscription-information-radio">
                    <input type="radio" name="secured_mode" value="individual" {{ $securedMode === 'individual' ? 'checked' : '' }}>
                    <span>One message per subscriber</span>
                </div>
            </div>

            <div id="secured-description-shared"{{ $securedMode === 'shared' ? '' : ' style="display: none;"' }}>
                <textarea name="secured_description_shared" cols="30" rows="5" class="textarea-sharesub" placeholder="Specify additional information such as the profile to be used. Optional except for invitation subscriptions: specify what you will need, such as an email.">{{ old('secured_description_shared') }}</textarea>
            </div>
            <div id="secured-description-individual"{{ $securedMode === 'individual' ? '' : ' style="display: none;"' }}>
                @for ($i = 0; $i < (int) ($offer->friends ?? 1); $i++)
                    <textarea name="secured_description_individual[]" cols="30" rows="1" class="textarea-sharesub" placeholder="Specify additional information such as the profile to be used. Optional except for invitation subscriptions: specify what you will need, such as an email.">{{ old('secured_description_individual.' . $i) }}</textarea>
                @endfor
            </div>
        </div>

        @if (! $hasCredentials)
            <div class="mt-2">
                <span class="text-danger">You must provide the login information to your future subscribers. Or explain the elements you will need to add/invite them.</span>
            </div>
        @endif

        <label class="subscription-information-accept" for="information-accept">
            <input type="checkbox" name="accept_tos" id="information-accept" value="1" {{ old('accept_tos') ? 'checked' : '' }} required>
            <span>I hereby certify that I am providing my identity and meet the access requirements for this share. I accept Sharesub’s <a href="{{ url('/cms/en/legal/tos') }}" target="_blank">Terms of Service</a> and Stripe’s <a href="https://stripe.com/fr/connect-account/legal#translation" target="_blank">Terms of Service</a>.</span>
        </label>

        <button type="submit" id="create-share-button" class="btn btn-sharesub green">Create and Share</button>
    </div>
</form>

<script>
(function () {
    var checkbox = document.getElementById('information-accept');
    var button = document.getElementById('create-share-button');
    var radios = document.querySelectorAll('input[name="secured_mode"]');
    var sharedBox = document.getElementById('secured-description-shared');
    var individualBox = document.getElementById('secured-description-individual');

    function syncButton() {
        if (checkbox && button) {
            button.disabled = !checkbox.checked;
        }
    }

    function syncDescriptions() {
        var mode = 'shared';
        radios.forEach(function (radio) {
            if (radio.checked) {
                mode = radio.value;
            }
        });
        if (sharedBox) {
            sharedBox.style.display = mode === 'shared' ? '' : 'none';
        }
        if (individualBox) {
            individualBox.style.display = mode === 'individual' ? '' : 'none';
        }
    }

    if (checkbox) {
        checkbox.addEventListener('change', syncButton);
    }

    radios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (checkbox) {
                checkbox.checked = false;
            }
            syncButton();
            syncDescriptions();
        });
    });

    syncButton();
    syncDescriptions();
})();
</script>
