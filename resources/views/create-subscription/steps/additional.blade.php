@php
    $credentialsType = $offer->template?->credentials_type ?? 0;
    $hasCredentials = $credentialsType !== 0;
@endphp

<form method="POST" action="{{ route('user.create-subscription.store') }}">
    @csrf

    @if ($offer->template?->helper_create)
        <div class="mb-3">{!! $offer->template->helper_create !!}</div>
    @else
        <p>Provide the information your members need to join your subscription.</p>
    @endif

    @if ($credentialsType === 6)
        <p class="text-muted text-sm">Your login details stay hidden from members. Only an invitation is sent.</p>
    @endif

    @if ($credentialsType === 1)
        <div class="row">
            <div class="col-12 col-md-6">
                <label for="credentials_login">Login</label>
                <input type="text" name="credentials_login" id="credentials_login" value="{{ old('credentials_login') }}" class="form-control" required>
            </div>
            <div class="col-12 col-md-6 mt-2 mt-md-0">
                <label for="credentials_password">Password</label>
                <input type="text" name="credentials_password" id="credentials_password" value="{{ old('credentials_password') }}" class="form-control" required>
            </div>
        </div>
    @elseif ($credentialsType === 2 || $credentialsType === 6)
        <div class="row">
            <div class="col-12 col-md-6">
                <label for="credentials_email">Email</label>
                <input type="email" name="credentials_email" id="credentials_email" value="{{ old('credentials_email') }}" class="form-control" required>
            </div>
            <div class="col-12 col-md-6 mt-2 mt-md-0">
                <label for="credentials_password">Password</label>
                <input type="text" name="credentials_password" id="credentials_password" value="{{ old('credentials_password') }}" class="form-control" required>
            </div>
        </div>
    @elseif ($credentialsType === 3)
        <div class="row">
            <div class="col-12 col-md-8">
                <label for="credentials_link">Invitation link</label>
                <input type="text" name="credentials_link" id="credentials_link" value="{{ old('credentials_link') }}" class="form-control" required>
            </div>
        </div>
    @elseif ($credentialsType === 4)
        <div class="row">
            <div class="col-12 col-md-6">
                <label for="credentials_link">Invitation link</label>
                <input type="text" name="credentials_link" id="credentials_link" value="{{ old('credentials_link') }}" class="form-control" required>
            </div>
            <div class="col-12 col-md-6 mt-2 mt-md-0">
                <label for="credentials_address">Address</label>
                <input type="text" name="credentials_address" id="credentials_address" value="{{ old('credentials_address') }}" class="form-control" required>
            </div>
        </div>
    @endif

    @if (! $hasCredentials)
        <p class="text-muted text-sm mt-3">Your subscription has no login to share, so tell members how you share it.</p>
        <div class="row mt-3">
            <div class="col-12">
                <label>
                    <input type="radio" name="secured_mode" value="shared" {{ old('secured_mode', 'shared') === 'shared' ? 'checked' : '' }}>
                    One description for the whole group
                </label>
            </div>
            <div class="col-12">
                <label>
                    <input type="radio" name="secured_mode" value="individual" {{ old('secured_mode') === 'individual' ? 'checked' : '' }}>
                    One description per member
                </label>
            </div>
        </div>
        @if (old('secured_mode') === 'individual')
            @for ($i = 0; $i < (int) ($offer->friends ?? 1); $i++)
                <div class="row mt-2">
                    <div class="col-12 col-md-8">
                        <label for="secured_description_individual_{{ $i }}">Description for member {{ $i + 1 }}</label>
                        <textarea name="secured_description_individual[]" id="secured_description_individual_{{ $i }}" rows="1" class="form-control">{{ old('secured_description_individual.' . $i) }}</textarea>
                    </div>
                </div>
            @endfor
        @else
            <div class="row mt-2">
                <div class="col-12 col-md-8">
                    <label for="secured_description_shared">How do you share this subscription?</label>
                    <textarea name="secured_description_shared" id="secured_description_shared" cols="30" rows="5" class="form-control">{{ old('secured_description_shared') }}</textarea>
                </div>
            </div>
        @endif
    @endif

    <div class="row mt-3">
        <div class="col-12">
            <label>
                <input type="checkbox" name="accept_tos" value="1" {{ old('accept_tos') ? 'checked' : '' }} required>
                I provide reliable information and a real identity. I accept the Sharesub terms of use and the Stripe terms of use.
            </label>
        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-sharesub green">Create subscription</button>
    </div>
</form>
