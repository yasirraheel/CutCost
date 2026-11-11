@php
    // The modal shows only when a real draft exists. $draft is an unrestored
    // draft passed by the controller when one is found (by hash or cookie).
    // Otherwise, fall back to the working offer, but only on a fresh entry
    // load of the create wizard: first step, no step requested yet, and the
    // user has not already resumed via ?draft=. Never on the edit page.
    $draftOffer = $draft ?? null;
    $draftHasProgress = $draftOffer !== null;

    if ($draftOffer === null && isset($offer) && $offer->status === 'draft' && request()->route('offer') === null && request()->query('step') === null && request()->query('draft') === null) {
        $draftOffer = $offer;
        $draftHasProgress = $offer->country_id !== null
            || $offer->brand_id !== null
            || $offer->template_id !== null
            || $offer->category_id !== null
            || ($offer->name !== null && trim((string) $offer->name) !== '')
            || $offer->visibility !== null;
    }

    $showDraftModal = $draftOffer !== null && $draftHasProgress && ! empty($draftOffer->hash);
    $draftName = $showDraftModal
        ? (trim((string) ($draftOffer->name ?? '')) !== '' ? $draftOffer->name : ($draftOffer->template?->name ?? $draftOffer->brand?->name ?? $draftOffer->category?->name ?? ''))
        : '';
@endphp

@if ($showDraftModal)
    <div class="report_modal" id="draft-modal" onclick="if (event.target === this) { this.style.display = 'none'; }">
        <div class="report_modal_content postion-relative">
            <div class="position-relative t6-bold text-center">
                Continuing to create
                <span class="cursor-pointer position-absolute top-0 right-0" onclick="document.getElementById('draft-modal').style.display = 'none';">
                    <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M23.3585 24.7184C23.7341 25.0939 24.3429 25.0939 24.7184 24.7184C25.0939 24.3429 25.0939 23.7341 24.7184 23.3586L16.9848 15.625L24.7184 7.89145C25.0939 7.51595 25.0939 6.90713 24.7184 6.53163C24.3429 6.15612 23.7341 6.15612 23.3586 6.53163L15.625 14.2652L7.89145 6.53163C7.51595 6.15612 6.90713 6.15612 6.53163 7.89145L14.2652 15.625L6.53163 23.3586C6.15612 23.7341 6.15612 24.3429 6.53163 24.7184C6.90713 25.0939 7.51594 25.0939 7.89145 24.7184L15.625 16.9848L23.3585 24.7184Z" fill="#828282" /></svg>
                </span>
            </div>
            <div>
                <div class="mt-3">
                    <p>If you've already started creating a subscription, you can pick up where you left off.</p>
                </div>
            </div>
            <div class="mt-3">
                <span class="subscription-draft-button" onclick="window.location.href='{{ route('user.create-subscription', ['draft' => $draftOffer->hash]) }}';"><img class="mr-2" src="/img/subscription/next.svg" loading="lazy" alt="">
                        Continue creating my subscription {{ $draftName }}
                    </span>
            </div>
            <div class="row justify-content-between p2-bold mt-5 flex-column flex-sm-row">
                <div class="text-blue d-flex align-items-center col-12 col-sm-auto order-2 order-sm-1 mt-3 mt-sm-0 justify-content-center justify-content-sm-start">
                    <form id="draft-cancel-form" method="POST" action="{{ route('user.create-subscription.destroy', $draftOffer) }}" class="d-none">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="redirect" value="create">
                    </form>
                    <div class="mr-3 cursor-pointer" onclick="document.getElementById('draft-cancel-form').submit();">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="11" fill="#016DF4" /><path d="M8.46436 8.46484L15.5354 15.5359" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /><path d="M15.5356 8.46484L8.46458 15.5359" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                            Cancel
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
