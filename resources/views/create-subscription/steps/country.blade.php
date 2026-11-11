<form method="GET" action="{{ route('user.create-subscription') }}" class="mb-3">
    <input type="hidden" name="step" value="country">
    <div class="row">
        <div class="col-12 col-md-6">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search for your country">
        </div>
        <div class="col-12 col-md-3 mt-2 mt-md-0">
            <button type="submit" class="btn-sharesub blue">Search</button>
        </div>
    </div>
</form>

<form method="POST" action="{{ route('user.create-subscription.step') }}">
    @csrf
    <input type="hidden" name="step" value="country">
    <ul class="dashboard-profile-steps mt-3">
        @forelse ($countries as $country)
            <li class="row align-items-center">
                <div class="col-1">
                    <input type="radio" name="country_id" id="country-{{ $country->id }}" value="{{ $country->id }}" {{ (int) old('country_id', $offer->country_id) === (int) $country->id ? 'checked' : '' }} required>
                </div>
                <div class="col">
                    @if ($country->flag_url)
                        <img src="{{ $country->flag_url }}" alt="{{ $country->name }} flag" style="width: 24px; height: 18px; margin-right: 6px; vertical-align: middle;">
                    @endif
                    <label for="country-{{ $country->id }}">{{ $country->name }}</label>
                </div>
            </li>
        @empty
            <li class="text-muted">No country matches your search.</li>
        @endforelse
    </ul>
    <div class="mt-3">
        <button type="submit" class="btn btn-sharesub green">Continue</button>
    </div>
</form>
