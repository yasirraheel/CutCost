<form method="POST" action="{{ route('user.create-subscription.step') }}">
    @csrf
    <input type="hidden" name="step" value="share">
    <div class="row">
        <div class="col-12 col-md-4">
            <label for="total_price">Total price</label>
            @if ($offer->template_id !== null)
                <div><strong>{{ $currencySymbol }}{{ number_format((float) ($offer->total_price ?? 0), 2) }} / month</strong></div>
                <div class="text-muted text-sm">Set by your plan.</div>
            @else
                <input type="number" name="total_price" id="total_price" value="{{ old('total_price', $offer->total_price) }}" step="0.01" min="0.01" class="form-control" required>
            @endif
        </div>
        <div class="col-12 col-md-4 mt-2 mt-md-0">
            <label for="friends">Seats you offer</label>
            <input type="number" name="friends" id="friends" value="{{ old('friends', $offer->friends) }}" min="1" max="{{ $maxUsers }}" class="form-control" required>
        </div>
        @if ($offer->template_id === null)
            <div class="col-12 col-md-4 mt-2 mt-md-0">
                <label for="total_seats">Total seats</label>
                <input type="number" name="total_seats" id="total_seats" value="{{ old('total_seats', $offer->total_seats) }}" min="2" max="{{ $maxUsers }}" class="form-control" required>
            </div>
        @endif
    </div>
    <div class="row mt-3">
        <div class="col-12">
            <label>
                <input type="checkbox" name="exclude" value="1" {{ old('exclude', (int) ($offer->share ?? 1) === 3) ? 'checked' : '' }}>
                Split the price over the offered seats only, so I pay nothing extra.
            </label>
        </div>
    </div>
    <ul class="dashboard-profile-steps mt-3">
        <li class="row align-items-center">
            <div class="col">Each member pays</div>
            <div class="col text-md-right"><strong>{{ $currencySymbol }}{{ number_format($shareMath['eachPays'], 2) }} / month</strong></div>
        </li>
        <li class="row align-items-center">
            <div class="col">You receive</div>
            <div class="col text-md-right"><strong>{{ $currencySymbol }}{{ number_format($shareMath['ownerPrice'], 2) }} / month</strong></div>
        </li>
        <li class="row align-items-center">
            <div class="col">Your net cost</div>
            <div class="col text-md-right"><strong>{{ $currencySymbol }}{{ number_format($shareMath['netCost'], 2) }} / month</strong></div>
        </li>
        <li class="row align-items-center">
            <div class="col">You save per year</div>
            <div class="col text-md-right"><strong>{{ $currencySymbol }}{{ number_format($shareMath['annualSaving'], 2) }}</strong></div>
        </li>
    </ul>
    <div class="mt-3">
        <button type="submit" class="btn btn-sharesub green">Continue</button>
    </div>
</form>
