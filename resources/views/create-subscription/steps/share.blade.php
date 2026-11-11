@php
    $hasTemplate = $offer->template_id !== null;
    $friends = (int) old('friends', $offer->friends ?? 3);
    $totalSeats = (int) old('total_seats', $offer->total_seats ?? $friends);
    $subPrice = $hasTemplate ? (float) ($offer->total_price ?? 0) : (float) (old('total_price', $offer->total_price) ?? 0);
    $share = (int) ($offer->share ?? 1);
    $frequency = (int) ($offer->frequency ?? 2);
    $periodLabels = [1 => '/week', 2 => '/ month', 3 => '/ quarter', 4 => '/ year'];
    $period = $periodLabels[$frequency] ?? '/ month';
    $frequencyMultipliers = [1 => 52, 2 => 12, 3 => 4, 4 => 1];
    $frequencyMultiplier = $frequencyMultipliers[$frequency] ?? 12;
    $displayedMaxUsers = $hasTemplate
        ? min($offer->template->shareable_seats ?? 1, ($offer->total_seats ?? 2) - 1) + 1
        : $totalSeats;
    $hasSavings = $shareMath['total'] > 0 && $shareMath['ownerPrice'] > 0 && $shareMath['netCost'] >= 0;
    $shareDisabled = $friends < 1 || $subPrice <= 0 || $shareMath['ownerPrice'] <= 0 || $shareMath['eachPays'] <= 0;
    $money = function ($value) use ($currencySymbol) {
        $formatted = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
        return $currencySymbol.$formatted;
    };
@endphp
<div class="subscription-share">
    <form method="POST" action="{{ route('user.create-subscription.step') }}" id="share-form" data-has-template="{{ $hasTemplate ? '1' : '0' }}" data-share="{{ $share }}" data-max-users="{{ $maxUsers }}" data-multiplier="{{ $frequencyMultiplier }}" data-symbol="{{ $currencySymbol }}" data-sub-price="{{ number_format($subPrice, 2, '.', '') }}" data-total-seats="{{ $totalSeats }}">
        @csrf
        <input type="hidden" name="step" value="share">
        @if ($hasTemplate)
            <p class="subscription-share-title">Subscription price <span>{{ $offer->template->name }}</span></p>
            <div class="subscription-share-infos">
                <div class="subscription-share-info users">
                    <img src="/img/subscription/people.svg" alt="icon" loading="lazy">
                    <span><strong>{{ $displayedMaxUsers }}</strong> maximum <strong> users</strong> </span>
                </div>
                <div class="subscription-share-info price">
                    <img src="/img/subscription/wallet.svg" alt="icon" loading="lazy">
                    <span>Total price of the subscription: <strong>{{ $money($subPrice) }}</strong><strong>{{ $period }}</strong></span>
                </div>
            </div>
        @else
            <p class="subscription-share-title">Price of the <span>personalized</span> subscription</p>
        @endif
        <div class="row">
            @if (!$hasTemplate)
                <div class="col">
                    <div class="subscription-share-places">
                        <div class="subscription-share-places-title">Monthly subscription price</div>
                        <div class="subscription-share-places-input">
                            <input type="number" name="total_price" id="total-price" class="input-sharesub" placeholder="15.99€" step="any" min="0" value="{{ old('total_price', $offer->total_price) }}">
                        </div>
                    </div>
                </div>
            @endif
            <div class="col">
                <div class="subscription-share-places">
                    <div class="subscription-share-places-title">{{ $hasTemplate ? 'How many slots would you like to share?' : 'Slots available' }}</div>
                    <div class="subscription-share-places-input">
                        <button type="button" id="friends-minus" class="{{ $friends > 1 ? 'text-green' : 'text-grey' }}">
                            <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="15.9997" cy="16" r="14.6667" fill="currentColor" /><rect x="6.66602" y="14.6667" width="18.6667" height="2.66667" rx="1.33333" fill="white" /></svg>
                        </button>
                        <input type="number" name="friends" id="friends" class="input-sharesub" min="1" max="{{ $maxUsers }}" value="{{ $friends }}">
                        <button type="button" id="friends-plus" class="{{ $friends < $maxUsers ? 'text-green' : 'text-grey' }}">
                            <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="15.9997" cy="16" r="14.6667" fill="currentColor" /><rect x="6.66602" y="14.6667" width="18.6667" height="2.66667" rx="1.33333" fill="white" /><rect x="17.333" y="6.66669" width="18.6667" height="2.66667" rx="1.33333" transform="rotate(90 17.333 6.66669)" fill="white" /></svg>
                        </button>
                    </div>
                </div>
            </div>
            @if (!$hasTemplate)
                <div class="col">
                    <div class="subscription-share-places">
                        <div class="subscription-share-places-title">Total slots</div>
                        <div class="subscription-share-places-input">
                            <button type="button" id="total-seats-minus" class="text-green">
                                <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="15.9997" cy="16" r="14.6667" fill="currentColor" /><rect x="6.66602" y="14.6667" width="18.6667" height="2.66667" rx="1.33333" fill="white" /></svg>
                            </button>
                            <input type="number" name="total_seats" id="total-seats" class="input-sharesub" min="2" max="{{ $maxUsers }}" value="{{ $totalSeats }}">
                            <button type="button" id="total-seats-plus" class="text-green">
                                <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="15.9997" cy="16" r="14.6667" fill="currentColor" /><rect x="6.66602" y="14.6667" width="18.6667" height="2.66667" rx="1.33333" fill="white" /><rect x="17.333" y="6.66669" width="18.6667" height="2.66667" rx="1.33333" transform="rotate(90 17.333 6.66669)" fill="white" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <div class="subscription-share-pay">Each person pays you<br class="d-md-none"> <strong id="each-pays">{{ $money($shareMath['eachPays']) }}</strong><strong>{{ $period }}</strong></div>
        <div class="subscription-share-receive">You will receive<br class="d-md-none"> <span id="owner-price">{{ $money($shareMath['ownerPrice']) }}</span><span>{{ $period }}</span> in total</div>
        <div class="subscription-share-savings" id="share-savings"{{ $hasSavings ? '' : ' hidden' }}>
            <div class="subscription-share-savings-row"><span>Your net cost after sharing</span> <strong id="net-cost">{{ $money($shareMath['netCost']) }}</strong><strong>{{ $period }}</strong></div>
            <div class="subscription-share-savings-badge"><span>💰</span> <span>You save <strong id="annual-saving-amount">{{ $money($shareMath['annualSaving']) }}</strong> per year thanks to sharing</span></div>
        </div>
        <button type="submit" class="btn btn-sharesub green" id="share-continue"{{ $shareDisabled ? ' disabled' : '' }}>Continue</button>
    </form>
</div>

<script>
(function () {
    var form = document.getElementById('share-form');
    if (!form) return;

    var hasTemplate = form.dataset.hasTemplate === '1';
    var share = parseInt(form.dataset.share, 10) || 1;
    var maxUsersLocal = parseInt(form.dataset.maxUsers, 10) || 12;
    var multiplier = parseInt(form.dataset.multiplier, 10) || 12;
    var symbol = form.dataset.symbol || '';
    var fixedSubPrice = parseFloat(form.dataset.subPrice) || 0;
    var fixedTotalSeats = parseInt(form.dataset.totalSeats, 10) || 0;

    var friendsInput = document.getElementById('friends');
    var totalSeatsInput = document.getElementById('total-seats');
    var subPriceInput = document.getElementById('total-price');
    var friendsMinus = document.getElementById('friends-minus');
    var friendsPlus = document.getElementById('friends-plus');
    var totalSeatsMinus = document.getElementById('total-seats-minus');
    var totalSeatsPlus = document.getElementById('total-seats-plus');
    var eachPaysEl = document.getElementById('each-pays');
    var ownerPriceEl = document.getElementById('owner-price');
    var netCostEl = document.getElementById('net-cost');
    var annualSavingEl = document.getElementById('annual-saving-amount');
    var savingsBox = document.getElementById('share-savings');
    var continueButton = document.getElementById('share-continue');

    function formatMoney(value) {
        var fixed = (Math.round(value * 100) / 100).toFixed(2).replace(/\.?0+$/, '');
        return symbol + fixed;
    }

    function currentSubPrice() {
        if (hasTemplate) return fixedSubPrice;
        if (!subPriceInput) return 0;
        var value = parseFloat(subPriceInput.value);
        return isNaN(value) ? 0 : value;
    }

    function currentFriends() {
        var value = parseInt(friendsInput.value, 10);
        return isNaN(value) ? 0 : value;
    }

    function currentTotalSeats() {
        if (hasTemplate) return fixedTotalSeats;
        if (!totalSeatsInput) return currentFriends();
        var value = parseInt(totalSeatsInput.value, 10);
        return isNaN(value) ? 0 : value;
    }

    function eachPaysFor(subPrice, friends, totalSeats) {
        // Plain division, no early rounding: the original's pricePerFriend
        // is the raw quotient and every amount rounds only at display,
        // like its Uo() formatter (see the controller's shareMath).
        if (subPrice <= 0) return 0;
        if (share === 1) return subPrice / Math.max(1, totalSeats);
        if (share === 3) return subPrice / Math.max(1, friends);
        return subPrice / Math.max(1, friends + 1);
    }

    function clampFriends(value) {
        var totalSeats = currentTotalSeats();
        if (!hasTemplate && (value >= totalSeats || value < 1)) value = totalSeats;
        if (value >= maxUsersLocal || !value || value < 0) value = maxUsersLocal;
        return value;
    }

    function clampTotalSeats(value) {
        if (value >= maxUsersLocal || !value || value < 0) value = maxUsersLocal;
        return value;
    }

    function recalc() {
        var subPrice = currentSubPrice();
        var friends = currentFriends();
        var totalSeats = currentTotalSeats();
        var eachPays = eachPaysFor(subPrice, friends, totalSeats);
        var ownerPrice = eachPays * friends;
        var netCost = Math.max(0, subPrice - ownerPrice);
        var annualSaving = ownerPrice * multiplier;

        if (eachPaysEl) eachPaysEl.textContent = formatMoney(eachPays);
        if (ownerPriceEl) ownerPriceEl.textContent = formatMoney(ownerPrice);
        if (netCostEl) netCostEl.textContent = formatMoney(netCost);
        if (annualSavingEl) annualSavingEl.textContent = formatMoney(annualSaving);
        if (savingsBox) savingsBox.hidden = !(subPrice > 0 && ownerPrice > 0 && netCost >= 0);
        if (continueButton) continueButton.disabled = friends < 1 || subPrice <= 0 || ownerPrice <= 0 || eachPays <= 0;
        if (friendsMinus) friendsMinus.className = friends > 1 ? 'text-green' : 'text-grey';
        if (friendsPlus) friendsPlus.className = friends < maxUsersLocal ? 'text-green' : 'text-grey';
    }

    if (friendsMinus) {
        friendsMinus.addEventListener('click', function () {
            var friends = currentFriends();
            if (friends > 1) {
                friendsInput.value = friends - 1;
                recalc();
            }
        });
    }

    if (friendsPlus) {
        friendsPlus.addEventListener('click', function () {
            var friends = currentFriends();
            if (friends < maxUsersLocal) {
                friendsInput.value = friends + 1;
                recalc();
            }
        });
    }

    if (totalSeatsMinus) {
        totalSeatsMinus.addEventListener('click', function () {
            var totalSeats = currentTotalSeats();
            if (totalSeats > 2) {
                totalSeatsInput.value = totalSeats - 1;
                if (currentFriends() > currentTotalSeats()) friendsInput.value = currentTotalSeats();
                recalc();
            }
        });
    }

    if (totalSeatsPlus) {
        totalSeatsPlus.addEventListener('click', function () {
            var totalSeats = currentTotalSeats();
            if (totalSeats < maxUsersLocal) {
                totalSeatsInput.value = totalSeats + 1;
                recalc();
            }
        });
    }

    if (friendsInput) {
        friendsInput.addEventListener('input', recalc);
        friendsInput.addEventListener('change', function () {
            friendsInput.value = clampFriends(currentFriends());
            recalc();
        });
    }

    if (totalSeatsInput) {
        totalSeatsInput.addEventListener('input', function () {
            if (currentFriends() > currentTotalSeats()) friendsInput.value = currentTotalSeats();
            recalc();
        });
        totalSeatsInput.addEventListener('change', function () {
            totalSeatsInput.value = clampTotalSeats(currentTotalSeats());
            if (currentFriends() > currentTotalSeats()) friendsInput.value = currentTotalSeats();
            recalc();
        });
    }

    if (subPriceInput) subPriceInput.addEventListener('input', recalc);

    recalc();
})();
</script>
