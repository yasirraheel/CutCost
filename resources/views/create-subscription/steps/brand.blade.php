<form method="GET" action="{{ route('user.create-subscription') }}" class="mb-3">
    <input type="hidden" name="step" value="brand">
    <div class="row">
        <div class="col-12 col-md-6">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search for a brand">
        </div>
        <div class="col-12 col-md-3 mt-2 mt-md-0">
            <button type="submit" class="btn-sharesub blue">Search</button>
        </div>
    </div>
</form>

<form method="POST" action="{{ route('user.create-subscription.step') }}">
    @csrf
    <input type="hidden" name="step" value="brand">
    <ul class="dashboard-profile-steps mt-3">
        @forelse ($brands as $brand)
            <li class="row align-items-center">
                <div class="col-1">
                    <input type="radio" name="brand_id" id="brand-{{ $brand->id }}" value="{{ $brand->id }}" {{ (int) old('brand_id', $offer->brand_id) === (int) $brand->id ? 'checked' : '' }}>
                </div>
                <div class="col">
                    <label for="brand-{{ $brand->id }}"><strong>{{ $brand->name }}</strong></label>
                </div>
            </li>
        @empty
            <li class="text-muted">No brand matches your search.</li>
        @endforelse
        <li class="row align-items-center">
            <div class="col-1">
                <input type="radio" name="brand_id" id="brand-custom" value="" {{ old('brand_id', $offer->brand_id) === null && $offer->custom_price ? 'checked' : '' }}>
            </div>
            <div class="col">
                <label for="brand-custom"><strong>I have another subscription</strong></label>
                <div class="text-muted text-sm">Share a subscription from a brand that is not listed.</div>
            </div>
        </li>
    </ul>
    <div class="mt-3">
        <button type="submit" class="btn btn-sharesub green">Continue</button>
    </div>
</form>
