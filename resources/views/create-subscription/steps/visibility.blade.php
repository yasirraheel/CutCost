<div class="subscription-visibility">
    <p class="subscription-visibility-title">Visibility for your subscription <span>{{ $offer->template?->name ?? '' }}</span></p>

    <form method="POST" action="{{ route('user.create-subscription.step') }}" id="visibility-form">
        @csrf
        <input type="hidden" name="step" value="visibility">
        <input type="hidden" name="confirm_private" id="confirm-private" value="1" disabled>
        <div class="row subscription-visibility-items">
            <div class="col-12 col-md-4">
                <label class="subscription-visibility-item{{ $offer->template_id === null ? ' disabled' : '' }}" title="{{ $offer->template_id === null ? 'Not available for custom subscriptions' : '' }}" data-toggle="tooltip" data-placement="top">
                    <input type="radio" name="visibility" value="1" class="d-none"{{ $offer->template_id === null ? ' disabled' : '' }}{{ (int) old('visibility', $offer->visibility ?? -1) === 1 ? ' checked' : '' }}>
                    <div>
                        <div class="subscription-visibility-item-title{{ $offer->template_id === null ? ' disabled' : '' }}">Public</div>
                        <div class="subscription-visibility-item-description">
                            Your subscription will be displayed in the list of available subscriptions on the site
                            <div class="mt-2"><span>⭐️</span> The fastest way to find subscribers</div>
                        </div>
                    </div>
                </label>
            </div>
            <div class="col-12 col-md-4">
                <label class="subscription-visibility-item" title="" data-toggle="tooltip" data-placement="top">
                    <input type="radio" name="visibility" value="2" class="d-none"{{ (int) old('visibility', $offer->visibility ?? -1) === 2 ? ' checked' : '' }}>
                    <div>
                        <div class="subscription-visibility-item-title">Locally</div>
                        <div class="subscription-visibility-item-description">
                            Attention: Your subscription will only be visible via "Local Search"
                            <div class="mt-2">📍 Useful for subscriptions which cannot be shared publicly</div>
                        </div>
                    </div>
                </label>
            </div>
            <div class="col-12 col-md-4">
                <label class="subscription-visibility-item" title="" data-toggle="tooltip" data-placement="top">
                    <input type="radio" name="visibility" value="0" class="d-none"{{ old('visibility', $offer->visibility) !== null && (int) old('visibility', $offer->visibility) === 0 ? ' checked' : '' }}>
                    <div>
                        <div class="subscription-visibility-item-title">Private</div>
                        <div class="subscription-visibility-item-description">
                            Only people to whom you communicate the secure link can join your subscription
                            <div class="mt-2">🔒 Privacy and customizable subscription options</div>
                        </div>
                    </div>
                </label>
            </div>
        </div>
    </form>

    <div class="report_modal" id="private-visibility-modal" hidden>
        <div class="report_modal_content postion-relative">
            <div class="position-relative t6-bold text-center">
                Private subscription
                <span class="cursor-pointer position-absolute top-0 right-0" id="private-visibility-modal-close">
                    <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M23.3585 24.7184C23.7341 25.0939 24.3429 25.0939 24.7184 24.7184C25.0939 24.3429 25.0939 23.7341 24.7184 23.3586L16.9848 15.625L24.7184 7.89145C25.0939 7.51595 25.0939 6.90713 24.7184 6.53163C24.3429 6.15612 23.7341 6.15612 23.3586 6.53163L15.625 14.2652L7.89145 6.53163C7.51595 6.15612 6.90713 6.15612 6.53163 6.53163C6.15612 6.90713 6.15612 7.51594 6.53163 7.89145L14.2652 15.625L6.53163 23.3586C6.15612 23.7341 6.15612 24.3429 6.53163 24.7184C6.90713 25.0939 7.51594 25.0939 7.89145 24.7184L15.625 16.9848L23.3585 24.7184Z" fill="#828282" /></svg>
                </span>
            </div>
            <div>
                <div class="mt-3">
                    <p>You have chosen the "Private" option: This means that your subscription will be visible only to you, and will not be displayed in the Sharesub lists.</p>
                    <p>No buyer will be able to access your subscription unless you send them the invitation link.</p>
                </div>
                <div class="row justify-content-around p2-bold mt-4 flex-column flex-sm-row">
                    <div class="text-blue d-flex align-items-center col-12 col-sm-auto order-2 order-sm-1 mt-3 mt-sm-0 justify-content-center justify-content-sm-start">
                        <div class="cursor-pointer" id="private-visibility-modal-cancel">Cancel</div>
                    </div>
                    <button type="button" class="btn-sharesub bleu" id="private-visibility-modal-confirm">I confirm</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('visibility-form');
    var modal = document.getElementById('private-visibility-modal');
    var confirmPrivate = document.getElementById('confirm-private');
    var closeButton = document.getElementById('private-visibility-modal-close');
    var cancelButton = document.getElementById('private-visibility-modal-cancel');
    var confirmButton = document.getElementById('private-visibility-modal-confirm');

    if (!form || !modal) return;

    function openModal() { modal.hidden = false; }
    function closeModal() { modal.hidden = true; }

    function selectVisibility(value) {
        if (value === '0') {
            openModal();
        } else {
            form.submit();
        }
    }

    form.addEventListener('change', function (event) {
        if (event.target && event.target.name === 'visibility') {
            selectVisibility(event.target.value);
        }
    });

    // Clicking the already-selected card fires no change event, so run
    // the same branch here. The guard skips the synthetic click on the
    // radio itself, which would double-submit with the change handler.
    form.addEventListener('click', function (event) {
        var label = event.target.closest ? event.target.closest('label') : null;
        if (!label) return;
        var radio = label.querySelector('input[name="visibility"]');
        if (radio && event.target !== radio && radio.checked) {
            selectVisibility(radio.value);
        }
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });

    if (closeButton) closeButton.addEventListener('click', closeModal);
    if (cancelButton) cancelButton.addEventListener('click', closeModal);

    if (confirmButton) {
        confirmButton.addEventListener('click', function () {
            if (confirmPrivate) confirmPrivate.disabled = false;
            closeModal();
            form.submit();
        });
    }
})();
</script>
