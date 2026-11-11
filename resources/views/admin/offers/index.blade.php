<section class="dashboard-progress mb-3 p-4">
    <h3 class="dashboard-progress-title">Offers</h3>
    <div class="progress mt-3" style="height: 10px;">
        <div class="progress-bar bg-green" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif
    <div class="mt-3">
        <a class="btn-sharesub bleu" href="{{ route('admin.offers.create') }}">Add offer</a>
    </div>
    <ul class="dashboard-profile-steps mt-3">
        @forelse ($offers as $offer)
            <li class="row align-items-center">
                <div class="col-1 text-right">🎟️</div>
                <div class="col">
                    <strong>{{ $offer->title }}</strong>
                    <div class="text-muted text-sm">{{ $offer->currency }} {{ number_format($offer->price, 2) }} · {{ $offer->brand?->name ?? 'No brand' }} · {{ $offer->seller_name ?? 'No seller' }} · {{ $offer->seats_available !== null ? $offer->seats_available.' seats left' : 'Seats unknown' }} · {{ $offer->is_active ? 'Active' : 'Inactive' }}</div>
                </div>
                <div class="col-12 col-md-4 offset-1 offset-md-0 text-md-right">
                    @include('admin.partials.row-actions', ['editUrl' => route('admin.offers.edit', $offer), 'deleteUrl' => route('admin.offers.destroy', $offer), 'deleteConfirm' => 'Delete this offer permanently?'])
                </div>
            </li>
        @empty
            <li class="text-muted">No offers found.</li>
        @endforelse
    </ul>
    <div class="mt-3">{{ $offers->links() }}</div>
</section>
