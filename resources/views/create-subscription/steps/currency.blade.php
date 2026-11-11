<form method="POST" action="{{ route('user.create-subscription.step') }}" id="currency-form">
    @csrf
    <input type="hidden" name="step" value="currency">
    <div class="subscription-template">
        <p class="subscription-template-title">Currency of your subscription</p>
        <div class="row subscription-template-items">
            @foreach ($currencies as $currency)
                <div class="col-2 col-md-4">
                    <label class="subscription-template-item">
                        <input class="d-none" type="radio" name="currency" value="{{ $currency['code'] }}" {{ old('currency', $offer->currency) === $currency['code'] ? 'checked' : '' }}>
                        <div class="subscription-template-item-title">{{ $currency['name'] }}</div>
                        <div class="subscription-template-item-infos"><span class="text-bold">{{ $currency['symbol'] }}</span> {{ $currency['code'] }}</div>
                    </label>
                </div>
            @endforeach
        </div>
    </div>
</form>

<script>
    (function () {
        var form = document.getElementById('currency-form');
        if (!form) return;
        form.addEventListener('change', function (event) {
            if (event.target && event.target.name === 'currency') {
                form.submit();
            }
        });
        // Clicking the already-selected tile fires no change event, so
        // advance here. The guard skips the synthetic click on the radio
        // itself, which would double-submit with the change handler.
        form.addEventListener('click', function (event) {
            var label = event.target.closest ? event.target.closest('label') : null;
            if (!label) return;
            var radio = label.querySelector('input[name="currency"]');
            if (radio && event.target !== radio && radio.checked) {
                form.submit();
            }
        });
    })();
</script>
