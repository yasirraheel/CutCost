<div class="subscription-brand">
    <div class="row subscription-brand-header">
        <div class="col-12 col-xl-6">
            <p class="subscription-brand-title">Which subscription do you share?</p>
        </div>
        <div class="col-12 col-xl-12 order-xl-last">
            <p class="subscription-brand-subtitle mt-xl-2">Search from <b>over 300 brands</b>, or select one of the main brands.</p>
        </div>
        <div class="col-12 col-xl-6">
            <form method="GET" action="{{ route('user.create-subscription') }}" id="brand-search-form">
                <input type="hidden" name="step" value="brand">
                <div class="subscription-brand-search">
                    <img class="subscription-brand-search-icon" src="{{ asset('img/subscription/search.svg') }}" alt="search" loading="lazy">
                    <input type="search" name="search" value="{{ request('search') }}" class="subscription-brand-search-input" placeholder="Search for a subscription..." id="brand-search-input">
                </div>
            </form>
        </div>
    </div>

    @php($selectedBrandId = old('brand_id', $offer->brand_id))

    <form method="POST" action="{{ route('user.create-subscription.step') }}" id="brand-form">
        @csrf
        <input type="hidden" name="step" value="brand">
        <div class="row subscription-brand-items">
            @foreach ($brands as $brand)
                <div class="col-6 col-md-3">
                    <label class="subscription-brand-item{{ $selectedBrandId !== null && $selectedBrandId !== '' && (int) $selectedBrandId === (int) $brand->id ? ' selected' : '' }}">
                        <input type="radio" name="brand_id" value="{{ $brand->id }}" class="d-none"{{ $selectedBrandId !== null && $selectedBrandId !== '' && (int) $selectedBrandId === (int) $brand->id ? ' checked' : '' }}>
                        <div><div class="p2-bold">{{ $brand->name }}</div></div>
                    </label>
                </div>
            @endforeach
        </div>
        <div class="mt-4 row subscription-brand-items">
            <div class="col-12 col-md-6">
                <label class="subscription-brand-item custom{{ $selectedBrandId === null || $selectedBrandId === '' ? ' selected' : '' }}">
                    <input type="radio" name="brand_id" value="" class="d-none"{{ $selectedBrandId === null || $selectedBrandId === '' ? ' checked' : '' }}>
                    <div><div class="p2-bold">Custom</div><div class="p2-regular">Allows you to share any type of monthly fee privately: wifi, roommate fees...</div></div>
                </label>
            </div>
            <div class="col-12 col-md-6">
                <label class="subscription-brand-item custom" onclick="window.open('/cms/en/suggest-a-new-sharesub-service/', '_blank').focus()">
                    <div><div class="p2-bold">Suggest a service</div></div>
                </label>
            </div>
        </div>
    </form>
</div>

<script>
(function () {
    var searchForm = document.getElementById('brand-search-form');
    var searchInput = document.getElementById('brand-search-input');
    var brandForm = document.getElementById('brand-form');

    if (searchForm && searchInput) {
        var searchTimer = null;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                searchForm.submit();
            }, 300);
        });
    }

    if (brandForm) {
        brandForm.addEventListener('change', function (event) {
            if (event.target && event.target.name === 'brand_id') {
                brandForm.submit();
            }
        });
        // Clicking the already-selected tile fires no change event (the
        // Custom tile starts checked on a fresh draft), so advance here.
        // The guard skips the synthetic click on the radio itself, which
        // would otherwise double-submit with the change handler.
        brandForm.addEventListener('click', function (event) {
            var label = event.target.closest ? event.target.closest('label') : null;
            if (!label) return;
            var radio = label.querySelector('input[name="brand_id"]');
            if (radio && event.target !== radio && radio.checked) {
                brandForm.submit();
            }
        });
    }
})();
</script>
