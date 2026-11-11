<section class="dashboard-progress mb-3 p-4">
    <div class="d-flex justify-content-between align-items-center">
        <h3 class="dashboard-progress-title mb-0">Offers</h3>
        <a class="btn-sharesub bleu" style="width: auto;" href="{{ route('admin.offers.create') }}">Add offer</a>
    </div>
    <div class="d-flex mt-3" style="gap: 8px; flex-wrap: wrap;">
        <a class="{{ $status === 'all' ? 'btn-sharesub bleu' : 'btn btn-light btn-sm' }}" style="width: auto;" href="{{ route('admin.offers.index') }}">All ({{ $counts['all'] }})</a>
        <a class="{{ $status === 'pending' ? 'btn-sharesub bleu' : 'btn btn-light btn-sm' }}" style="width: auto;" href="{{ route('admin.offers.index', ['status' => 'pending']) }}">Pending review ({{ $counts['pending'] }})</a>
        <a class="{{ $status === 'active' ? 'btn-sharesub bleu' : 'btn btn-light btn-sm' }}" style="width: auto;" href="{{ route('admin.offers.index', ['status' => 'active']) }}">Active ({{ $counts['active'] }})</a>
        <a class="{{ $status === 'rejected' ? 'btn-sharesub bleu' : 'btn btn-light btn-sm' }}" style="width: auto;" href="{{ route('admin.offers.index', ['status' => 'rejected']) }}">Rejected ({{ $counts['rejected'] }})</a>
    </div>
    <div class="progress mt-3" style="height: 10px;">
        <div class="progress-bar bg-green" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif
    <ul class="dashboard-profile-steps mt-3">
        @forelse ($offers as $offer)
            <li class="row align-items-center">
                <div class="col-1 text-right">@include('admin.partials.brand-icon', ['brand' => $offer->brand, 'fallback' => '🎟️'])</div>
                <div class="col">
                    <strong>{{ $offer->title }}</strong>
                    <div class="text-muted text-sm">{{ $offer->currency }} {{ number_format($offer->price, 2) }} · {{ $offer->brand?->name ?? 'No brand' }} · {{ $offer->seller_name ?? 'No seller' }} · {{ $offer->seats_available !== null ? $offer->seats_available.' seats left' : 'Seats unknown' }} · {{ $offer->is_active ? 'Active' : 'Inactive' }} · {{ $offer->statusLabel() }}</div>
                </div>
                <div class="col-12 col-md-4 offset-1 offset-md-0 text-md-right">
                    <div class="d-flex align-items-center justify-content-md-end" style="gap: 8px;">
                        @if ($offer->isPendingReview())
                            <form class="d-inline m-0" method="POST" action="{{ route('admin.offers.approve', $offer) }}">
                                @csrf
                                <button type="submit" class="btn btn-light btn-sm">Approve</button>
                            </form>
                            <form class="d-inline m-0" method="POST" action="{{ route('admin.offers.reject', $offer) }}">
                                @csrf
                                <button type="submit" class="btn-sharesub red outline small">Reject</button>
                            </form>
                        @endif
                        @include('admin.partials.row-actions', ['editUrl' => route('admin.offers.edit', $offer), 'deleteUrl' => route('admin.offers.destroy', $offer), 'deleteConfirm' => 'Delete this offer permanently?'])
                    </div>
                </div>
            </li>
        @empty
            <li class="text-muted">No offers found.</li>
        @endforelse
    </ul>
    <div class="mt-3">{{ $offers->links() }}</div>
</section>
