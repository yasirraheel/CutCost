<section class="dashboard-progress mb-3 p-4">
    <div class="d-flex justify-content-between align-items-center">
        <h3 class="dashboard-progress-title mb-0">Pages</h3>
        <a class="btn-sharesub bleu" style="width: auto;" href="{{ route('admin.pages.create') }}">Add page</a>
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
        @forelse ($pages as $page)
            <li class="row align-items-center">
                <div class="col-1 text-right">📄</div>
                <div class="col">
                    @if ($page->image_path)<img src="{{ str_starts_with($page->image_path, '/') || str_starts_with($page->image_path, 'http') ? asset($page->image_path) : asset('storage/'.$page->image_path) }}" alt="" style="max-height: 40px; max-width: 60px; margin-right: 8px; vertical-align: middle;">@endif<strong>{{ $page->title }}</strong>
                    <div class="text-muted text-sm">{{ ucfirst($page->type) }} · {{ $page->slug }} · Order {{ $page->sort_order }} · {{ $page->is_active ? 'Active' : 'Inactive' }}</div>
                </div>
                <div class="col-12 col-md-4 offset-1 offset-md-0 text-md-right">
                    @include('admin.partials.row-actions', ['editUrl' => route('admin.pages.edit', $page), 'deleteUrl' => route('admin.pages.destroy', $page), 'deleteConfirm' => 'Delete this page permanently?'])
                </div>
            </li>
        @empty
            <li class="text-muted">No pages found.</li>
        @endforelse
    </ul>
    <div class="mt-3">{{ $pages->links() }}</div>
</section>
