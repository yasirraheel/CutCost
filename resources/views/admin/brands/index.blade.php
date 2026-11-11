<section class="dashboard-progress mb-3 p-4">
    <div class="d-flex justify-content-between align-items-center">
        <h3 class="dashboard-progress-title mb-0">Brands</h3>
        <a class="btn-sharesub bleu" style="width: auto;" href="{{ route('admin.brands.create') }}">Add brand</a>
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
        @forelse ($brands as $brand)
            <li class="row align-items-center">
                <div class="col-1 text-right">🏷️</div>
                <div class="col">
                    @if ($brand->logo_path)<img src="{{ str_starts_with($brand->logo_path, '/') || str_starts_with($brand->logo_path, 'http') ? asset($brand->logo_path) : asset('storage/'.$brand->logo_path) }}" alt="" style="max-height: 40px; max-width: 60px; margin-right: 8px; vertical-align: middle;">@endif<strong>{{ $brand->name }}</strong>
                    <div class="text-muted text-sm">{{ $brand->currency_symbol }}{{ number_format($brand->price, 2) }} · {{ $brand->slug }} · {{ $brand->category?->name ?? 'No category' }} · {{ $brand->is_active ? 'Active' : 'Inactive' }}{{ $brand->is_featured ? ' · Featured' : '' }}</div>
                </div>
                <div class="col-12 col-md-4 offset-1 offset-md-0 text-md-right">
                    @include('admin.partials.row-actions', ['editUrl' => route('admin.brands.edit', $brand), 'deleteUrl' => route('admin.brands.destroy', $brand), 'deleteConfirm' => 'Delete this brand permanently?'])
                </div>
            </li>
        @empty
            <li class="text-muted">No brands found.</li>
        @endforelse
    </ul>
    <div class="mt-3">{{ $brands->links() }}</div>
</section>
