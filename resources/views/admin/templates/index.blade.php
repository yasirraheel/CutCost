<section class="dashboard-progress mb-3 p-4">
    <h3 class="dashboard-progress-title">Templates</h3>
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
        <a class="btn-sharesub bleu" href="{{ route('admin.templates.create') }}">Add template</a>
    </div>
    <ul class="dashboard-profile-steps mt-3">
        @forelse ($templates as $template)
            <li class="row align-items-center">
                <div class="col-1 text-right">📦</div>
                <div class="col">
                    <strong>{{ $template->name }}</strong>
                    <div class="text-muted text-sm">{{ $template->currency }} {{ number_format($template->price_with_fees, 2) }} · {{ $template->brand?->name ?? 'No brand' }} · {{ $template->slug }} · {{ $template->is_active ? 'Active' : 'Inactive' }}</div>
                </div>
                <div class="col-12 col-md-4 offset-1 offset-md-0 text-md-right">
                    @include('admin.partials.row-actions', ['editUrl' => route('admin.templates.edit', $template), 'deleteUrl' => route('admin.templates.destroy', $template), 'deleteConfirm' => 'Delete this template permanently?'])
                </div>
            </li>
        @empty
            <li class="text-muted">No templates found.</li>
        @endforelse
    </ul>
    <div class="mt-3">{{ $templates->links() }}</div>
</section>
