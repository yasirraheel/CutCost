@php
    // Period labels from the captured en locale (localization.js,
    // subscription.create.period_adj_1 to 4), keyed by template frequency.
    $periodLabels = [1 => '/week', 2 => '/ month', 3 => '/ quarter', 4 => '/ year'];
    $selectedTemplateId = old('template_id', $offer->template_id);
    // The original's own computed name: brand selected and no templates.
    $brandHasTemplate = $offer->brand_id !== null && $templates->isEmpty();
@endphp
<div class="subscription-template">
    <p class="subscription-template-title">{{ $brandHasTemplate ? 'Which subscription are you sharing ?' : 'Describe your subscription' }}</p>
    @if ($offer->brand_id !== null)
        <form method="POST" action="{{ route('user.create-subscription.step') }}" id="template-step-form">
            @csrf
            <input type="hidden" name="step" value="template">
            <div class="row subscription-template-items">
                @foreach ($templates as $template)
                    @php
                        // Price mirrors the controller's templatePayload: the
                        // retail price, else the price with fees.
                        $templatePrice = $template->retail_price ?? $template->price_with_fees ?? 0;
                        $templateSymbol = $currencySymbols[$template->currency] ?? $currencySymbol;
                        $templateMaxUsers = min($template->shareable_seats ?? 1, ($template->seats ?? 2) - 1) + 1;
                        $templatePeriod = $periodLabels[(int) ($template->frequency ?? 2)] ?? $periodLabels[2];
                    @endphp
                    <div class="col-12 col-md-6">
                        <label class="subscription-template-item{{ (int) $selectedTemplateId === (int) $template->id ? ' selected' : '' }}">
                            <input type="radio" name="template_id" value="{{ $template->id }}" class="d-none" {{ (int) $selectedTemplateId === (int) $template->id ? 'checked' : '' }}>
                            <div class="subscription-template-item-title-row">
                                <div class="subscription-template-item-title">{{ $template->name }}</div>
                            </div>
                            <div class="subscription-template-item-infos">
                                <div class="subscription-template-item-info user">
                                    <img src="/img/subscription/people.svg" alt="icon" loading="lazy">
                                    <span>{{ $templateMaxUsers }} users max</span>
                                </div>
                                <div class="subscription-template-item-info price">{{ $templateSymbol }}{{ number_format((float) $templatePrice, 2) }}{{ $templatePeriod }}</div>
                            </div>
                        </label>
                    </div>
                @endforeach
                @if ($brandHasTemplate)
                    <div class="col-12 col-md-6">
                        <label class="subscription-template-item{{ $selectedTemplateId === null || $selectedTemplateId === '' ? ' selected' : '' }}">
                            <input type="radio" name="template_id" value="" class="d-none" {{ $selectedTemplateId === null || $selectedTemplateId === '' ? 'checked' : '' }}>
                            <div class="subscription-template-item-title">
                                <div class="p2-bold">Custom</div>
                            </div>
                        </label>
                    </div>
                @endif
            </div>
        </form>
        <script>
            (function () {
                var form = document.getElementById('template-step-form');
                if (!form) return;
                form.addEventListener('change', function (event) {
                    if (event.target && event.target.name === 'template_id') {
                        form.submit();
                    }
                });
                // Clicking the already-selected card fires no change event,
                // so advance here. The guard skips the synthetic click on
                // the radio itself, which would double-submit.
                form.addEventListener('click', function (event) {
                    var label = event.target.closest ? event.target.closest('label') : null;
                    if (!label) return;
                    var radio = label.querySelector('input[name="template_id"]');
                    if (radio && event.target !== radio && radio.checked) {
                        form.submit();
                    }
                });
            })();
        </script>
    @else
        <form method="POST" action="{{ route('user.create-subscription.step') }}">
            @csrf
            <input type="hidden" name="step" value="template">
            <div class="row p2-regular">
                <div class="col-12 col-xl-6 mb-4">
                    <span class="mb-2">Give it a short name</span>
                    <input type="text" name="name" id="custom-template-name" value="{{ old('name', $offer->name) }}" maxlength="80" placeholder="ex. Subscription to New York Times" class="input-sharesub" required>
                </div>
            </div>
            <div class="row p2-regular">
                <div class="col">
                    <span class="mb-2 mt-4">Description of the service to explain the offer you share</span>
                    <textarea name="description" cols="30" rows="4" placeholder="ex. I have an additional subscription available on my plan to this amazing newspaper, and I’m willing to share it with you. I will provide the necessary information for you to join once registered." class="input-sharesub">{{ old('description', $offer->description) }}</textarea>
                </div>
            </div>
            <button type="submit" class="btn-sharesub green px-5 mt-4 mb-5" id="custom-template-continue" {{ old('name', $offer->name) ? '' : 'disabled' }}>Continue</button>
        </form>
        <script>
            (function () {
                var nameInput = document.getElementById('custom-template-name');
                var button = document.getElementById('custom-template-continue');
                if (nameInput && button) {
                    nameInput.addEventListener('input', function () {
                        button.disabled = !nameInput.value || nameInput.value.length === 0;
                    });
                }
            })();
        </script>
    @endif
</div>
