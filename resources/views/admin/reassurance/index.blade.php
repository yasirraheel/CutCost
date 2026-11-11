<section class="dashboard-progress mb-3 p-4">
    <h3 class="dashboard-progress-title">Reassurance</h3>
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
        <a class="btn-sharesub bleu" href="{{ route('admin.reassurance.create') }}">Add reassurance item</a>
    </div>
    <ul class="dashboard-profile-steps mt-3">
        @forelse ($items as $item)
            <li class="row align-items-center">
                <div class="col-1 text-right">✅</div>
                <div class="col">
                    <strong>{{ $item->title }}</strong>
                    <div class="text-muted text-sm">{{ Str::limit($item->text, 80) }} · Order {{ $item->sort_order }} · {{ $item->is_active ? 'Active' : 'Inactive' }}</div>
                </div>
                <div class="col-12 col-md-4 offset-1 offset-md-0 text-md-right">
                    @include('admin.partials.row-actions', ['editUrl' => route('admin.reassurance.edit', $item), 'deleteUrl' => route('admin.reassurance.destroy', $item), 'deleteConfirm' => 'Delete this reassurance item permanently?'])
                </div>
            </li>
        @empty
            <li class="text-muted">No reassurance items found.</li>
        @endforelse
    </ul>
    <div class="mt-3">{{ $items->links() }}</div>
</section>
