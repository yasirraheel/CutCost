@php
    // Top countries, from the capture: the countries prop rows with top_country 1
    // (Belgium, France, Germany, Italy, Spain, United Kingdom), in the order the
    // controller's TOP_COUNTRIES list and the user's screenshot show them.
    $topCountryNames = ['Belgium', 'France', 'Germany', 'Italy', 'Spain', 'United Kingdom'];
    $allCountries = collect($countries);
    $selectedCountryId = old('country_id', $offer->country_id);
    if (($selectedCountryId === null || $selectedCountryId === '') && $allCountries->isNotEmpty()) {
        // The original preselects the visitor's country on mount; the controller's
        // default country is the United Kingdom.
        $defaultCountry = $allCountries->firstWhere('name', 'United Kingdom');
        $selectedCountryId = $defaultCountry !== null ? $defaultCountry->id : null;
    }
    $selectedCountryId = $selectedCountryId !== null && $selectedCountryId !== '' ? (int) $selectedCountryId : null;
    $searching = request()->filled('search');
    $topCountries = $allCountries
        ->filter(fn ($country) => in_array($country->name, $topCountryNames, true))
        ->sortBy(fn ($country) => array_search($country->name, $topCountryNames, true))
        ->values();
    if ($selectedCountryId !== null && ! $topCountries->contains('id', $selectedCountryId)) {
        // The original prepends the current country to the top row when it is not
        // already a top country.
        $selectedCountry = $allCountries->firstWhere('id', $selectedCountryId);
        if ($selectedCountry !== null) {
            $topCountries->prepend($selectedCountry);
        }
    }
    $otherCountries = $allCountries->reject(fn ($country) => $topCountries->contains('id', $country->id))->values();
    $displayedCountries = $searching ? $allCountries : $otherCountries;
@endphp

<div class="subscription-country" id="country-step">
    <div class="subscription-country-header">
        <p class="subscription-country-title">What country is the subscription from?</p>
    </div>
    <form method="POST" action="{{ route('user.create-subscription.step') }}" id="country-form">
        @csrf
        <input type="hidden" name="step" value="country">
        @if ($selectedCountryId !== null)
            <input type="hidden" name="country_id" value="{{ $selectedCountryId }}">
        @endif
        @if ($topCountries->isNotEmpty() && ! $searching)
            <div class="subscription-country-top">
                <div class="subscription-country-top-items">
                    @foreach ($topCountries as $country)
                        <label class="subscription-country-compact-item{{ $selectedCountryId !== null && (int) $country->id === $selectedCountryId ? ' selected' : '' }}">
                            <input type="radio" name="country_id" value="{{ $country->id }}" class="d-none"{{ $selectedCountryId !== null && (int) $country->id === $selectedCountryId ? ' checked' : '' }}>
                            @if ($country->flag_url)
                                <img class="subscription-country-compact-item-flag" src="{{ $country->flag_url }}" alt="{{ $country->name }}" loading="lazy">
                            @endif
                            <span>{{ $country->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="subscription-country-separator">
                <span>Other countries</span>
            </div>
        @endif
        <div class="subscription-country-search">
            <img class="subscription-country-search-icon" src="{{ asset('img/subscription/search.svg') }}" alt="search" loading="lazy">
            <input type="search" name="search" value="{{ request('search') }}" class="subscription-country-search-input" placeholder="Search for a country..." id="country-search-input" data-search-url="{{ route('user.create-subscription', ['step' => 'country']) }}">
        </div>
        <div class="subscription-country-list">
            @foreach ($displayedCountries as $country)
                <label class="subscription-country-compact-item{{ $selectedCountryId !== null && (int) $country->id === $selectedCountryId ? ' selected' : '' }}">
                    <input type="radio" name="country_id" value="{{ $country->id }}" class="d-none"{{ $selectedCountryId !== null && (int) $country->id === $selectedCountryId ? ' checked' : '' }}>
                    @if ($country->flag_url)
                        <img class="subscription-country-compact-item-flag" src="{{ $country->flag_url }}" alt="{{ $country->name }}" loading="lazy">
                    @endif
                    <span>{{ $country->name }}</span>
                </label>
            @endforeach
        </div>
        @if ($searching && $allCountries->count() < 5)
            <div class="subscription-country-not-found">
                @if ($allCountries->isEmpty())
                    <p class="text-muted mt-2">No countries found.</p>
                @endif
                <a class="btn btn-sharesub-outline mt-2" href="https://ask.sharesub.com/country-waitlist" target="_blank" rel="noopener">Request the addition of my country</a>
            </div>
        @endif
        @if ($selectedCountryId !== null)
            <button type="submit" class="btn btn-sharesub green mt-4">Continue</button>
        @endif
    </form>
</div>

<script>
(function () {
    var step = document.getElementById('country-step');
    var searchInput = document.getElementById('country-search-input');

    if (step) {
        step.addEventListener('change', function (event) {
            if (event.target && event.target.name === 'country_id') {
                var labels = step.querySelectorAll('.subscription-country-compact-item');
                labels.forEach(function (label) {
                    label.classList.remove('selected');
                });
                var label = event.target.closest('.subscription-country-compact-item');
                if (label) {
                    label.classList.add('selected');
                }
            }
        });
    }

    if (searchInput) {
        var searchTimer = null;
        var goToSearch = function () {
            var baseUrl = searchInput.getAttribute('data-search-url');
            var value = searchInput.value.trim();
            window.location.assign(value === '' ? baseUrl : baseUrl + '&search=' + encodeURIComponent(value));
        };
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(goToSearch, 300);
        });
        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                clearTimeout(searchTimer);
                goToSearch();
            }
        });
    }
})();
</script>
