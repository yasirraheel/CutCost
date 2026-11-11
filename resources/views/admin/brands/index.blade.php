<section class="dashboard-progress mb-3 p-4">
    <h3 class="dashboard-progress-title">Brands</h3>
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
        <a class="btn-sharesub bleu" href="{{ route('admin.brands.create') }}">Add brand</a>
    </div>
    <ul class="dashboard-profile-steps mt-3">
        @forelse ($brands as $brand)
            <li class="row align-items-center">
                <div class="col-1 text-right">🏷️</div>
                <div class="col">
                    <strong>{{ $brand->name }}</strong>
                    <div class="text-muted text-sm">{{ $brand->currency_symbol }}{{ number_format($brand->price, 2) }} · {{ $brand->slug }} · {{ $brand->category?->name ?? 'No category' }} · {{ $brand->is_active ? 'Active' : 'Inactive' }}{{ $brand->is_featured ? ' · Featured' : '' }}</div>
                </div>
                <div class="col-12 col-md-4 offset-1 offset-md-0 text-md-right">
                    <a class="btn btn-light btn-sm" href="{{ route('admin.brands.edit', $brand) }}">Edit</a>
                    <form class="d-inline" method="POST" action="{{ route('admin.brands.destroy', $brand) }}" onsubmit="return confirm('Delete this brand permanently?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-sharesub red outline small">Delete</button>
                    </form>
                </div>
            </li>
        @empty
            <li class="text-muted">No brands found.</li>
        @endforelse
    </ul>
    <div class="mt-3">{{ $brands->links() }}</div>
</section>
