<form method="POST" action="{{ route('user.create-subscription.step') }}">
    @csrf
    <input type="hidden" name="step" value="currency">
    <ul class="dashboard-profile-steps mt-3">
        @foreach ($currencies as $currency)
            <li class="row align-items-center">
                <div class="col-1">
                    <input type="radio" name="currency" id="currency-{{ $currency['code'] }}" value="{{ $currency['code'] }}" {{ old('currency', $offer->currency) === $currency['code'] ? 'checked' : '' }} required>
                </div>
                <div class="col">
                    <label for="currency-{{ $currency['code'] }}"><strong>{{ $currency['symbol'] }} {{ $currency['code'] }}</strong></label>
                    <div class="text-muted text-sm">{{ $currency['name'] }}</div>
                </div>
            </li>
        @endforeach
    </ul>
    <div class="mt-3">
        <button type="submit" class="btn btn-sharesub green">Continue</button>
    </div>
</form>
