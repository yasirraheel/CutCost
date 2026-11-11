<section class="dashboard-progress mb-3 p-4">
    <h3 class="dashboard-progress-title">Press</h3>
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
        <a class="btn-sharesub bleu" href="{{ route('admin.press.create') }}">Add press item</a>
    </div>
    <ul class="dashboard-profile-steps mt-3">
        @forelse ($pressItems as $pressItem)
            <li class="row align-items-center">
                <div class="col-1 text-right">📰</div>
                <div class="col">
                    <strong>{{ $pressItem->outlet_name }}</strong>
                    <div class="text-muted text-sm">{{ $pressItem->logo_text }} · {{ $pressItem->link_url }} · Order {{ $pressItem->sort_order }} · {{ $pressItem->is_active ? 'Active' : 'Inactive' }}</div>
                </div>
                <div class="col-12 col-md-4 offset-1 offset-md-0 text-md-right">
                    <a class="btn btn-light btn-sm" href="{{ route('admin.press.edit', $pressItem) }}">Edit</a>
                    <form class="d-inline" method="POST" action="{{ route('admin.press.destroy', $pressItem) }}" onsubmit="return confirm('Delete this press item permanently?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-sharesub red outline small">Delete</button>
                    </form>
                </div>
            </li>
        @empty
            <li class="text-muted">No press items found.</li>
        @endforelse
    </ul>
    <div class="mt-3">{{ $pressItems->links() }}</div>
</section>
