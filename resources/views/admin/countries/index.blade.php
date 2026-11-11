<section class="dashboard-progress mb-3 p-4">
    <div class="d-flex justify-content-between align-items-center">
        <h3 class="dashboard-progress-title mb-0">Countries</h3>
        <a class="btn-sharesub bleu" style="width: auto;" href="{{ route('admin.countries.create') }}">Add country</a>
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
        @forelse ($countries as $country)
            <li class="row align-items-center">
                <div class="col-1 text-right">
                    @if ($country->flag_url)
                        <img src="{{ $country->flag_url }}" alt="{{ $country->name }} flag" style="width: 24px; height: 18px;">
                    @else
                        🌍
                    @endif
                </div>
                <div class="col">
                    <strong>{{ $country->name }}</strong>
                    <div class="text-muted text-sm">{{ $country->code }} · {{ $country->currency }} · Fee {{ $country->fee_flat }} + {{ $country->fee_percent }} · {{ $country->is_active ? 'Active' : 'Inactive' }}</div>
                </div>
                <div class="col-12 col-md-4 offset-1 offset-md-0 text-md-right">
                    @include('admin.partials.row-actions', ['editUrl' => route('admin.countries.edit', $country), 'deleteUrl' => route('admin.countries.destroy', $country), 'deleteConfirm' => 'Delete this country permanently?'])
                </div>
            </li>
        @empty
            <li class="text-muted">No countries found.</li>
        @endforelse
    </ul>
    <div class="mt-3">{{ $countries->links() }}</div>
</section>
